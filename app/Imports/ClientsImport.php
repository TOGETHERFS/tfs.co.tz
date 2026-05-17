<?php

namespace App\Imports;

use App\Models\Client;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Illuminate\Support\Facades\DB;

class ClientsImport implements ToCollection, WithHeadingRow, WithCustomCsvSettings, WithCalculatedFormulas
{
    public int $created = 0;
    public int $updated = 0;
    public array $errors = [];

    protected int $tenantId;

    public function __construct(int $tenantId)
    {
        $this->tenantId = $tenantId;
        // Reset heading formatter to default snake_case
        HeadingRowFormatter::default('slug');
    }

    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ',',
            'enclosure' => '"',
            'input_encoding' => 'UTF-8',
        ];
    }

    public function collection(Collection $rows)
    {
        logger()->info('ClientsImport: Starting import', [
            'total_rows' => $rows->count(),
            'tenant_id' => $this->tenantId,
            'first_row_keys' => $rows->first() ? array_keys($rows->first()->toArray()) : [],
        ]);
        
        // Store total rows for debugging
        $this->errors[] = "DEBUG: Total rows in file: " . $rows->count();
        
        foreach ($rows as $index => $row) {
            try {
                $rowArray = $row->toArray();
                
                logger()->info('ClientsImport: Processing row', [
                    'index' => $index,
                    'row_data' => $rowArray,
                ]);
                
                // Skip truly empty rows
                if (empty(array_filter($rowArray, fn($v) => $v !== null && $v !== ''))) {
                    logger()->info('ClientsImport: Skipping empty row', ['index' => $index]);
                    $this->errors[] = "Row " . ($index + 2) . ": skipped (empty row)";
                    continue;
                }
                
                $data = $this->sanitizeRow($rowArray);

                if (empty($data['id_number'])) {
                    $this->errors[] = "Row " . ($index + 2) . ": missing id_number (got: " . json_encode(array_keys($rowArray)) . ")";
                    continue;
                }

                $existing = Client::withoutGlobalScope('tenant')
                    ->where('tenant_id', $this->tenantId)
                    ->where('id_number', $data['id_number'])
                    ->first();

                if ($existing) {
                    $existing->update($data);
                    $this->updated++;
                } else {
                    $data['tenant_id'] = $this->tenantId;
                    // Use createWithRetry to handle duplicate client_id race conditions
                    Client::createWithRetry($data);
                    $this->created++;
                }
            } catch (\Throwable $e) {
                $this->errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
            }
        }
    }

    protected function sanitizeRow(array $row): array
    {
        $firstName = trim((string)($row['first_name'] ?? ''));
        $lastName = trim((string)($row['last_name'] ?? ''));
        $phone = trim((string)($row['phone'] ?? ''));
        $email = trim((string)($row['email'] ?? ''));
        $address = trim((string)($row['address'] ?? ''));
        $region = trim((string)($row['region'] ?? ''));
        $district = trim((string)($row['district'] ?? ''));
        $ward = trim((string)($row['ward'] ?? ''));
        $street = trim((string)($row['street'] ?? ''));
        $idNumber = trim((string)($row['id_number'] ?? ''));
        $gender = strtolower(trim((string)($row['gender'] ?? 'other')));
        $status = strtolower(trim((string)($row['status'] ?? 'active')));
        $branchName = trim((string)($row['branch_name'] ?? ''));
        $loanOfficer = trim((string)($row['loan_officer'] ?? ''));

        $dobRaw = $row['date_of_birth'] ?? null;
        $dateOfBirth = null;
        if (!is_null($dobRaw) && $dobRaw !== '') {
            if (is_numeric($dobRaw)) {
                try {
                    $dateOfBirth = Carbon::instance(ExcelDate::excelToDateTimeObject($dobRaw))->toDateString();
                } catch (\Throwable $e) {
                    $dateOfBirth = null;
                }
            } else {
                try {
                    $dateOfBirth = Carbon::parse($dobRaw)->toDateString();
                } catch (\Throwable $e) {
                    $dateOfBirth = null;
                }
            }
        }

        if (!in_array($gender, ['male', 'female', 'other'])) {
            $gender = 'other';
        }

        if (!in_array($status, ['active', 'inactive', 'blacklisted'])) {
            $status = 'active';
        }

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'email' => $email ?: null,
            'address' => $address ?: null,
            'region' => $region ?: null,
            'district' => $district ?: null,
            'ward' => $ward ?: null,
            'street' => $street ?: null,
            'date_of_birth' => $dateOfBirth,
            'gender' => $gender,
            'id_number' => $idNumber,
            'status' => $status,
            'branch_name' => $branchName ?: null,
            'loan_officer' => $loanOfficer ?: null,
        ];
    }
}