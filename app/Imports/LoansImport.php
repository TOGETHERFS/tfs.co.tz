<?php

namespace App\Imports;

use App\Models\Loan;
use App\Models\Client;
use App\Models\LoanProduct;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class LoansImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    protected int $tenantId;
    protected int $userId;

    protected int $created = 0;
    protected int $updated = 0;
    protected array $errors = [];

    public function __construct(int $tenantId, int $userId)
    {
        $this->tenantId = $tenantId;
        $this->userId   = $userId;
        HeadingRowFormatter::default('slug');
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            try {
                $raw  = $row->toArray();
                $data = $this->sanitizeRow($raw);

                // ── 1. Resolve client ──────────────────────────────────────────
                $client = $this->resolveClient($data, $index);
                if (!$client) continue;

                // ── 2. Resolve loan product ───────────────────────────────────
                $product = $this->resolveProduct($data, $index);
                if (!$product) continue;

                // ── 3. Build loan payload ─────────────────────────────────────
                $principal = $data['principal'];
                $term      = $data['term'];

                // Relax product limit validation for imported/historical loans
                if ($principal <= 0) {
                    $this->errors[] = "Row " . ($index + 2) . ": principal must be greater than 0.";
                    continue;
                }
                if ($term <= 0) {
                    $this->errors[] = "Row " . ($index + 2) . ": number of installments must be greater than 0.";
                    continue;
                }

                $interestRate   = $product->interest_rate;
                $monthlyPayment = $product->calculateMonthlyPayment($principal, $term);
                $totalAmount    = $data['total_interest'] > 0
                    ? $principal + $data['total_interest']
                    : $product->calculateTotalAmount($principal, $term);
                $processingFee  = $product->calculateProcessingFee($principal);

                $disbursementDate = $data['disbursement_date'];
                $firstPaymentDate = $disbursementDate
                    ? $disbursementDate->copy()->addMonth()->startOfDay()
                    : Carbon::now()->addMonth()->startOfDay();

                // Determine status from outstanding balance
                $outstandingPrincipal = $data['outstanding_principal'];
                $status = 'active';
                if ($outstandingPrincipal <= 0) {
                    $status = 'completed';
                }

                $loanNumber = $data['loan_number'] ?: $this->generateLoanNumber();

                $payload = [
                    'tenant_id'        => $this->tenantId,
                    'client_id'        => $client->id,
                    'product_id'       => $product->id,
                    'user_id'          => $this->userId,
                    'loan_number'      => $loanNumber,
                    'principal'        => $principal,
                    'interest_rate'    => $interestRate,
                    'term'             => $term,
                    'monthly_payment'  => $monthlyPayment,
                    'total_amount'     => $totalAmount,
                    'processing_fee'   => $processingFee,
                    'first_payment_date' => $firstPaymentDate,
                    'disbursed_at'     => $disbursementDate,
                    'status'           => $status,
                    'notes'            => $data['notes'],
                ];

                // ── 4. Create or update ───────────────────────────────────────
                $existing = Loan::withoutGlobalScope('tenant')
                    ->where('tenant_id', $this->tenantId)
                    ->where('loan_number', $loanNumber)
                    ->first();

                if ($existing) {
                    $existing->update($payload);
                    try { $existing->generateSchedule(); } catch (\Throwable $e) {}
                    $this->replayPaidAmountOnSchedules($existing, $data);
                    $this->updated++;
                } else {
                    $loan = Loan::create($payload);
                    try { $loan->generateSchedule(); } catch (\Throwable $e) {}
                    $this->replayPaidAmountOnSchedules($loan, $data);
                    $this->created++;
                }
            } catch (\Throwable $e) {
                $this->errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
            }
        }
    }

    // ── Client resolution ──────────────────────────────────────────────────────

    protected function resolveClient(array $data, int $index): ?Client
    {
        $client = null;

        // Try phone first (most reliable)
        if (!empty($data['phone'])) {
            $client = Client::withoutGlobalScope('tenant')
                ->where('tenant_id', $this->tenantId)
                ->where('phone', $data['phone'])
                ->first();
        }

        // Try ID number
        if (!$client && !empty($data['client_id_number'])) {
            $client = Client::withoutGlobalScope('tenant')
                ->where('tenant_id', $this->tenantId)
                ->where('id_number', $data['client_id_number'])
                ->first();
        }

        // Try by full name (case-insensitive)
        if (!$client && !empty($data['name'])) {
            $parts = explode(' ', $data['name'], 3);
            $firstName = $parts[0] ?? '';
            $lastName  = $parts[count($parts) - 1] ?? '';
            $client = Client::withoutGlobalScope('tenant')
                ->where('tenant_id', $this->tenantId)
                ->whereRaw('LOWER(first_name) = ?', [strtolower($firstName)])
                ->whereRaw('LOWER(last_name) = ?', [strtolower($lastName)])
                ->first();
        }

        // Auto-create client if phone is available
        if (!$client && !empty($data['phone']) && !empty($data['name'])) {
            $parts     = explode(' ', $data['name'], 3);
            $firstName = $parts[0] ?? $data['name'];
            $lastName  = $parts[count($parts) - 1] ?? '';
            try {
                $client = Client::create([
                    'tenant_id'  => $this->tenantId,
                    'first_name' => $firstName,
                    'last_name'  => $lastName ?: $firstName,
                    'phone'      => $data['phone'],
                    'email'      => $data['email'] ?: null,
                    'status'     => 'active',
                ]);
            } catch (\Throwable $e) {
                $this->errors[] = "Row " . ($index + 2) . ": could not create client — " . $e->getMessage();
                return null;
            }
        }

        if (!$client) {
            $this->errors[] = "Row " . ($index + 2) . ": client not found. Provide a valid Name or MobileNo.";
        }
        return $client;
    }

    // ── Product resolution ─────────────────────────────────────────────────────

    protected function resolveProduct(array $data, int $index): ?LoanProduct
    {
        $product = null;

        if (!empty($data['product_id'])) {
            $product = LoanProduct::withoutGlobalScope('tenant')
                ->where('tenant_id', $this->tenantId)
                ->where('id', $data['product_id'])
                ->first();
        }
        if (!$product && !empty($data['product_name'])) {
            $product = LoanProduct::withoutGlobalScope('tenant')
                ->where('tenant_id', $this->tenantId)
                ->whereRaw('LOWER(name) = ?', [strtolower($data['product_name'])])
                ->first();
            // Partial match fallback
            if (!$product) {
                $product = LoanProduct::withoutGlobalScope('tenant')
                    ->where('tenant_id', $this->tenantId)
                    ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($data['product_name']) . '%'])
                    ->first();
            }
        }

        if (!$product) {
            $this->errors[] = "Row " . ($index + 2) . ": loan product '{$data['product_name']}' not found.";
        }
        return $product;
    }

    // ── Row sanitiser ──────────────────────────────────────────────────────────

    protected function sanitizeRow(array $row): array
    {
        // Support both old column names and new Excel column names
        $name         = trim((string)($row['name'] ?? $row['borrower_name'] ?? ''));
        $phone        = $this->formatPhone(trim((string)($row['mobileno'] ?? $row['mobile_no'] ?? $row['mobile_number'] ?? $row['client_phone'] ?? '')));
        $email        = trim((string)($row['email_address'] ?? $row['email_addr'] ?? $row['emailaddr'] ?? $row['email'] ?? ''));
        $productName  = trim((string)($row['product_name'] ?? ''));
        $productId    = isset($row['product_id']) && is_numeric($row['product_id']) ? (int)$row['product_id'] : null;
        $idNumber     = trim((string)($row['client_id_number'] ?? $row['id_number'] ?? ''));
        $loanNumber   = trim((string)($row['loan_number'] ?? ''));
        $notes        = trim((string)($row['notes'] ?? ''));

        // Amounts — new format uses total_principal / old uses principal
        $principal           = (float)str_replace(',', '', (string)($row['total_principal'] ?? $row['total_princip'] ?? $row['principal'] ?? 0));
        $totalInterest       = (float)str_replace(',', '', (string)($row['total_interest'] ?? $row['total_interes'] ?? $row['totalinterest'] ?? 0));
        // Directly-supplied paid amounts from the new template (most reliable)
        $paidPrincipalCol    = (float)str_replace(',', '', (string)($row['paid_principal'] ?? $row['paidprincipal'] ?? -1));
        $paidInterestCol     = (float)str_replace(',', '', (string)($row['paid_interest']  ?? $row['paid_intres']  ?? $row['paidinterest'] ?? -1));
        $outstandingPrincipal = (float)str_replace(',', '', (string)(
            $row['outstanding_principal'] ?? $row['outstanding_prin']    ??
            $row['outstandingprincipal']  ?? $row['outstanding_balance'] ??
            $row['outstandingbalance']    ?? $row['remaining_principal'] ??
            $row['remainingprincipal']    ?? $row['principal_balance']   ??
            $row['balance_principal']     ?? 0
        ));
        $outstandingInterest  = (float)str_replace(',', '', (string)(
            $row['outstanding_interest']  ?? $row['outstandinginterest']  ??
            $row['remaining_interest']    ?? $row['remaininginterest']    ??
            $row['accrued_interest']      ?? $row['accruedinterest']      ?? 0
        ));

        // Term — new format uses no_of_installments / old uses term
        $term = (int)($row['no_of_installments'] ?? $row['no_of_installment'] ?? $row['no_of_installme'] ?? $row['installments'] ?? $row['term'] ?? 0);

        // Dates
        $disbursementDate = $this->parseDate($row['disbursement_date'] ?? $row['disburse_date'] ?? null);
        $firstPaymentDate = $this->parseDate($row['first_payment_date'] ?? null);
        if (!$firstPaymentDate && $disbursementDate) {
            $firstPaymentDate = $disbursementDate->copy()->addMonth()->startOfDay();
        }

        return [
            'name'                 => $name,
            'phone'                => $phone,
            'email'                => $email,
            'client_id_number'     => $idNumber,
            'product_name'         => $productName,
            'product_id'           => $productId,
            'principal'            => $principal,
            'total_interest'       => $totalInterest,
            'outstanding_principal' => $outstandingPrincipal,
            'outstanding_interest'  => $outstandingInterest,
            'paid_principal_col'    => $paidPrincipalCol,
            'paid_interest_col'     => $paidInterestCol,
            'term'                 => $term,
            'disbursement_date'    => $disbursementDate,
            'first_payment_date'   => $firstPaymentDate,
            'loan_number'          => $loanNumber,
            'notes'                => $notes,
        ];
    }

    protected function parseDate($value): ?Carbon
    {
        if ($value === null || $value === '') return null;
        if (is_numeric($value)) {
            try { return Carbon::instance(ExcelDate::excelToDateTimeObject((float)$value)); } catch (\Throwable $e) { return null; }
        }
        try { return Carbon::parse($value); } catch (\Throwable $e) { return null; }
    }

    protected function formatPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) === 9) return '255' . $phone;
        if (strlen($phone) === 10 && str_starts_with($phone, '0')) return '255' . substr($phone, 1);
        return $phone;
    }

    private function generateLoanNumber(): string
    {
        $prefix = 'LN';
        $year   = date('Y');
        $month  = date('m');
        $last   = Loan::withoutGlobalScope('tenant')
            ->where('tenant_id', $this->tenantId)
            ->where('loan_number', 'like', $prefix . $year . $month . '%')
            ->latest('id')
            ->first();
        $seq = $last ? (intval(substr($last->loan_number, -4)) + 1) : 1;
        return $prefix . $year . $month . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Replay already-paid amounts onto schedules so paid/partial/pending statuses are correct.
     *
     * Strategy 0: use direct paid_principal + paid_interest columns
     * Strategy 1: derive from outstanding balances  (total - outstanding = paid)
     * Strategy 2: infer paid count from outstanding_principal vs principal ratio (when interest
     *             is not supplied separately but principal has reduced)
     * Strategy 3: mark all paid when loan is completed and outstanding data is absent
     */
    protected function replayPaidAmountOnSchedules(Loan $loan, array $data): void
    {
        $outstandingPrincipal = (float) ($data['outstanding_principal'] ?? 0);
        $outstandingInterest  = (float) ($data['outstanding_interest']  ?? 0);
        $paidPrincipalCol     = (float) ($data['paid_principal_col']    ?? -1);
        $paidInterestCol      = (float) ($data['paid_interest_col']     ?? -1);
        $totalLoanAmount      = (float) $loan->total_amount;
        $loanPrincipal        = (float) $loan->principal;
        $loanTerm             = (int)   $loan->term;

        $disbursementDate = $data['disbursement_date'] ?? null;
        $paidDate = $disbursementDate ? $disbursementDate->toDateString() : null;

        $schedules = $loan->schedules()->orderBy('due_date')->orderBy('installment_number')->get();
        if ($schedules->isEmpty()) return;

        // Strategy 0 (highest priority): use direct paid_principal + paid_interest columns
        // This is the most accurate — the Excel explicitly says how much has been paid.
        $alreadyPaid = 0.0;
        if ($paidPrincipalCol >= 0) {
            $paidInt     = $paidInterestCol >= 0 ? $paidInterestCol : 0.0;
            $alreadyPaid = round($paidPrincipalCol + $paidInt, 2);
        }

        // Strategy 1: derive from outstanding balances  (total - outstanding = paid)
        if ($alreadyPaid <= 0 && ($outstandingPrincipal > 0 || $outstandingInterest > 0)) {
            $candidate = round($totalLoanAmount - ($outstandingPrincipal + $outstandingInterest), 2);
            if ($candidate > 0) {
                $alreadyPaid = $candidate;
            }
        }

        // Strategy 2: fallback via outstanding_principal reduction ratio
        if ($alreadyPaid <= 0
            && $outstandingPrincipal > 0
            && $loanPrincipal > 0
            && $outstandingPrincipal < $loanPrincipal
            && $loanTerm > 0
        ) {
            $paidPrincipal           = round($loanPrincipal - $outstandingPrincipal, 2);
            $principalPerInstallment = round($loanPrincipal / $loanTerm, 4);
            if ($principalPerInstallment > 0) {
                $paidCount = (int) floor($paidPrincipal / $principalPerInstallment);
                if ($paidCount > 0) {
                    $alreadyPaid = $schedules->take($paidCount)->sum(fn($s) => (float) $s->total_amount);
                    $alreadyPaid = round($alreadyPaid, 2);
                }
            }
        }

        // Strategy 3: loan is completed but no outstanding columns provided
        if ($alreadyPaid <= 0 && $loan->status === 'completed') {
            $alreadyPaid = $totalLoanAmount;
        }

        if ($alreadyPaid <= 0) return;

        $remaining = $alreadyPaid;
        foreach ($schedules as $schedule) {
            if ($remaining <= 0) break;

            $due  = (float) $schedule->total_amount;
            $pay  = min($remaining, $due);
            $remaining = round($remaining - $pay, 2);

            $schedule->paid_amount = round($pay, 2);
            if ($schedule->paid_amount >= $due) {
                $schedule->status    = 'paid';
                $schedule->paid_date = $paidDate;
            } elseif ($schedule->paid_amount > 0) {
                $schedule->status    = 'partial';
                $schedule->paid_date = null;
            }
            $schedule->save();
        }
    }

    public function getSummary(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'errors'  => $this->errors,
        ];
    }
}