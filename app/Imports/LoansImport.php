<?php

namespace App\Imports;

use App\Models\Loan;
use App\Models\Client;
use App\Models\LoanProduct;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
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
        $this->userId = $userId;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            try {
                $data = $this->sanitizeRow($row->toArray());

                // Find client by ID number or phone (within the same tenant)
                $client = null;
                if (!empty($data['client_id_number'])) {
                    $client = Client::withoutGlobalScope('tenant')
                        ->where('tenant_id', $this->tenantId)
                        ->where('id_number', $data['client_id_number'])
                        ->first();
                }
                if (!$client && !empty($data['client_phone'])) {
                    $client = Client::withoutGlobalScope('tenant')
                        ->where('tenant_id', $this->tenantId)
                        ->where('phone', $data['client_phone'])
                        ->first();
                }
                if (!$client) {
                    $this->errors[] = "Row " . ($index + 2) . ": client not found (id_number/phone required).";
                    continue;
                }

                // Find loan product by ID or name (within the same tenant)
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
                        ->where('name', $data['product_name'])
                        ->first();
                }
                if (!$product) {
                    $this->errors[] = "Row " . ($index + 2) . ": loan product not found (name/id required).";
                    continue;
                }

                // Validate principal & term against product limits
                $principal = (float) ($data['principal'] ?? 0);
                $term = (int) ($data['term'] ?? 0);
                if ($principal < $product->min_amount || $principal > $product->max_amount) {
                    $this->errors[] = "Row " . ($index + 2) . ": principal must be between {$product->min_amount} and {$product->max_amount}.";
                    continue;
                }
                if ($term < $product->min_term || $term > $product->max_term) {
                    $this->errors[] = "Row " . ($index + 2) . ": term must be between {$product->min_term} and {$product->max_term} months.";
                    continue;
                }

                // Compute loan details
                $monthlyPayment = $product->calculateMonthlyPayment($principal, $term);
                $totalAmount = $product->calculateTotalAmount($principal, $term);
                $processingFee = $product->calculateProcessingFee($principal);

                $firstPaymentDate = $data['first_payment_date'] ?? Carbon::now()->addMonth()->startOfDay();

                // Normalize status
                $status = $data['status'] ?? 'pending';
                $allowed = ['pending','approved','rejected','disbursed','active','completed','defaulted','overdue'];
                if (!in_array($status, $allowed)) {
                    $status = 'pending';
                }

                $loanNumber = $data['loan_number'] ?: $this->generateLoanNumber();

                // Find existing loan by number (within the same tenant)
                $existing = Loan::withoutGlobalScope('tenant')
                    ->where('tenant_id', $this->tenantId)
                    ->where('loan_number', $loanNumber)
                    ->first();

                $payload = [
                    'tenant_id' => $this->tenantId,
                    'client_id' => $client->id,
                    'product_id' => $product->id,
                    'user_id' => $this->userId,
                    'loan_number' => $loanNumber,
                    'principal' => $principal,
                    'interest_rate' => $product->interest_rate,
                    'term' => $term,
                    'monthly_payment' => $monthlyPayment,
                    'total_amount' => $totalAmount,
                    'processing_fee' => $processingFee,
                    'first_payment_date' => $firstPaymentDate,
                    'status' => $status,
                    'notes' => $data['notes'] ?? null,
                ];

                if ($existing) {
                    // Only allow updating of pending loans
                    if ($existing->status !== 'pending') {
                        $this->errors[] = "Row " . ($index + 2) . ": loan {$loanNumber} not updated (status={$existing->status}).";
                        continue;
                    }
                    $existing->update($payload);
                    $existing->generateSchedule();
                    $this->updated++;
                } else {
                    $loan = Loan::create($payload);
                    $loan->generateSchedule();
                    $this->created++;
                }
            } catch (\Throwable $e) {
                $this->errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
            }
        }
    }

    protected function sanitizeRow(array $row): array
    {
        $first = $row['first_payment_date'] ?? null;
        $firstDate = null;
        if ($first !== null && $first !== '') {
            if (is_numeric($first)) {
                try { $firstDate = Carbon::instance(ExcelDate::excelToDateTimeObject((float) $first)); } catch (\Throwable $e) { $firstDate = null; }
            } else {
                try { $firstDate = Carbon::parse($first); } catch (\Throwable $e) { $firstDate = null; }
            }
        }

        $status = isset($row['status']) ? strtolower(trim($row['status'])) : null;

        return [
            'client_id_number' => isset($row['client_id_number']) ? trim($row['client_id_number']) : null,
            'client_phone' => isset($row['client_phone']) ? trim($row['client_phone']) : null,
            'product_name' => isset($row['product_name']) ? trim($row['product_name']) : null,
            'product_id' => isset($row['product_id']) ? (int) $row['product_id'] : null,
            'principal' => isset($row['principal']) ? (float) $row['principal'] : null,
            'term' => isset($row['term']) ? (int) $row['term'] : null,
            'first_payment_date' => $firstDate,
            'loan_number' => isset($row['loan_number']) ? trim($row['loan_number']) : null,
            'status' => $status,
            'notes' => isset($row['notes']) ? trim($row['notes']) : null,
        ];
    }

    private function generateLoanNumber(): string
    {
        $prefix = 'LN';
        $year = date('Y');
        $month = date('m');
        $lastLoan = Loan::withoutGlobalScope('tenant')
                       ->where('tenant_id', $this->tenantId)
                       ->where('loan_number', 'like', $prefix . $year . $month . '%')
                       ->latest('id')
                       ->first();

        if ($lastLoan) {
            $lastNumber = intval(substr($lastLoan->loan_number, -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function getSummary(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'errors' => $this->errors,
        ];
    }
}