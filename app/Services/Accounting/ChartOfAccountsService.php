<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AccountCategory;
use App\Models\Accounting\ChartOfAccount;
use Illuminate\Support\Facades\DB;

class ChartOfAccountsService
{
    public function setupDefaultAccounts(int $tenantId): void
    {
        DB::transaction(function () use ($tenantId) {
            $this->createDefaultCategories($tenantId);
            $this->createDefaultAccounts($tenantId);
        });
    }

    protected function createDefaultCategories(int $tenantId): void
    {
        $categories = [
            ['code' => '1000', 'name' => 'Assets', 'type' => 'asset', 'normal_balance' => 'debit', 'sort_order' => 1],
            ['code' => '2000', 'name' => 'Liabilities', 'type' => 'liability', 'normal_balance' => 'credit', 'sort_order' => 2],
            ['code' => '3000', 'name' => 'Equity', 'type' => 'equity', 'normal_balance' => 'credit', 'sort_order' => 3],
            ['code' => '4000', 'name' => 'Income', 'type' => 'income', 'normal_balance' => 'credit', 'sort_order' => 4],
            ['code' => '5000', 'name' => 'Expenses', 'type' => 'expense', 'normal_balance' => 'debit', 'sort_order' => 5],
        ];

        foreach ($categories as $category) {
            AccountCategory::withoutGlobalScope('tenant')->updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => $category['code']],
                array_merge($category, ['tenant_id' => $tenantId, 'is_system' => true])
            );
        }
    }

    protected function createDefaultAccounts(int $tenantId): void
    {
        $categories = AccountCategory::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->get()
            ->keyBy('type');

        $accounts = $this->getMicrofinanceChartOfAccounts();

        foreach ($accounts as $account) {
            $category = $categories[$account['type']] ?? null;
            if (!$category) continue;

            ChartOfAccount::withoutGlobalScope('tenant')->updateOrCreate(
                ['tenant_id' => $tenantId, 'account_code' => $account['code']],
                [
                    'tenant_id' => $tenantId,
                    'category_id' => $category->id,
                    'account_code' => $account['code'],
                    'account_name' => $account['name'],
                    'description' => $account['description'] ?? null,
                    'account_type' => $account['type'],
                    'normal_balance' => $account['normal_balance'],
                    'is_active' => true,
                    'is_system' => $account['is_system'] ?? false,
                    'allow_manual_entry' => $account['allow_manual_entry'] ?? true,
                    'is_bank_account' => $account['is_bank_account'] ?? false,
                    'is_cash_account' => $account['is_cash_account'] ?? false,
                    'level' => $account['level'] ?? 1,
                ]
            );
        }
    }

    protected function getMicrofinanceChartOfAccounts(): array
    {
        return [
            // ASSETS (1000-1999)
            ['code' => '1100', 'name' => 'Cash on Hand', 'type' => 'asset', 'normal_balance' => 'debit', 'is_cash_account' => true, 'is_system' => true, 'description' => 'Physical cash held at branches'],
            ['code' => '1110', 'name' => 'Petty Cash', 'type' => 'asset', 'normal_balance' => 'debit', 'is_cash_account' => true],
            ['code' => '1200', 'name' => 'Cash at Bank', 'type' => 'asset', 'normal_balance' => 'debit', 'is_bank_account' => true, 'is_system' => true, 'description' => 'Bank account balances'],
            ['code' => '1210', 'name' => 'Operating Bank Account', 'type' => 'asset', 'normal_balance' => 'debit', 'is_bank_account' => true],
            ['code' => '1220', 'name' => 'Savings Bank Account', 'type' => 'asset', 'normal_balance' => 'debit', 'is_bank_account' => true],
            ['code' => '1300', 'name' => 'Mobile Money Wallets', 'type' => 'asset', 'normal_balance' => 'debit', 'is_system' => true, 'description' => 'Mobile money account balances'],
            ['code' => '1310', 'name' => 'M-Pesa Float', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '1320', 'name' => 'Tigo Pesa Float', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '1330', 'name' => 'Airtel Money Float', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '1400', 'name' => 'Loan Portfolio', 'type' => 'asset', 'normal_balance' => 'debit', 'is_system' => true, 'allow_manual_entry' => false, 'description' => 'Outstanding loan principal'],
            ['code' => '1410', 'name' => 'Loan Portfolio - Current', 'type' => 'asset', 'normal_balance' => 'debit', 'allow_manual_entry' => false],
            ['code' => '1420', 'name' => 'Loan Portfolio - Overdue', 'type' => 'asset', 'normal_balance' => 'debit', 'allow_manual_entry' => false],
            ['code' => '1430', 'name' => 'Loan Portfolio - Restructured', 'type' => 'asset', 'normal_balance' => 'debit', 'allow_manual_entry' => false],
            ['code' => '1500', 'name' => 'Interest Receivable', 'type' => 'asset', 'normal_balance' => 'debit', 'is_system' => true, 'allow_manual_entry' => false, 'description' => 'Accrued interest on loans'],
            ['code' => '1510', 'name' => 'Interest Receivable - Current', 'type' => 'asset', 'normal_balance' => 'debit', 'allow_manual_entry' => false],
            ['code' => '1520', 'name' => 'Interest Receivable - Overdue', 'type' => 'asset', 'normal_balance' => 'debit', 'allow_manual_entry' => false],
            ['code' => '1600', 'name' => 'Fees Receivable', 'type' => 'asset', 'normal_balance' => 'debit', 'is_system' => true, 'description' => 'Outstanding fees and charges'],
            ['code' => '1610', 'name' => 'Processing Fees Receivable', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '1620', 'name' => 'Penalty Fees Receivable', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '1700', 'name' => 'Prepaid Expenses', 'type' => 'asset', 'normal_balance' => 'debit', 'description' => 'Expenses paid in advance'],
            ['code' => '1710', 'name' => 'Prepaid Rent', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '1720', 'name' => 'Prepaid Insurance', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '1800', 'name' => 'Fixed Assets', 'type' => 'asset', 'normal_balance' => 'debit', 'is_system' => true, 'description' => 'Property, plant and equipment'],
            ['code' => '1810', 'name' => 'Office Equipment', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '1820', 'name' => 'Furniture & Fixtures', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '1830', 'name' => 'Computer Equipment', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '1840', 'name' => 'Motor Vehicles', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '1850', 'name' => 'Buildings', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '1860', 'name' => 'Land', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '1890', 'name' => 'Accumulated Depreciation', 'type' => 'asset', 'normal_balance' => 'credit', 'is_system' => true],
            ['code' => '1891', 'name' => 'Accum. Depreciation - Equipment', 'type' => 'asset', 'normal_balance' => 'credit'],
            ['code' => '1892', 'name' => 'Accum. Depreciation - Furniture', 'type' => 'asset', 'normal_balance' => 'credit'],
            ['code' => '1893', 'name' => 'Accum. Depreciation - Computers', 'type' => 'asset', 'normal_balance' => 'credit'],
            ['code' => '1894', 'name' => 'Accum. Depreciation - Vehicles', 'type' => 'asset', 'normal_balance' => 'credit'],
            ['code' => '1900', 'name' => 'Other Assets', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '1910', 'name' => 'Security Deposits', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '1950', 'name' => 'Loan Loss Reserve', 'type' => 'asset', 'normal_balance' => 'credit', 'is_system' => true, 'description' => 'Provision for bad loans'],

            // LIABILITIES (2000-2999)
            ['code' => '2100', 'name' => 'Client Savings / Deposits', 'type' => 'liability', 'normal_balance' => 'credit', 'is_system' => true, 'allow_manual_entry' => false, 'description' => 'Client deposit accounts'],
            ['code' => '2110', 'name' => 'Regular Savings', 'type' => 'liability', 'normal_balance' => 'credit', 'allow_manual_entry' => false],
            ['code' => '2120', 'name' => 'Compulsory Savings', 'type' => 'liability', 'normal_balance' => 'credit', 'allow_manual_entry' => false],
            ['code' => '2130', 'name' => 'Fixed Deposits', 'type' => 'liability', 'normal_balance' => 'credit', 'allow_manual_entry' => false],
            ['code' => '2200', 'name' => 'Borrowed Funds', 'type' => 'liability', 'normal_balance' => 'credit', 'is_system' => true, 'description' => 'Loans from banks and investors'],
            ['code' => '2210', 'name' => 'Bank Loans Payable', 'type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => '2220', 'name' => 'Investor Loans Payable', 'type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => '2300', 'name' => 'Accounts Payable', 'type' => 'liability', 'normal_balance' => 'credit', 'is_system' => true, 'description' => 'Amounts owed to suppliers'],
            ['code' => '2310', 'name' => 'Trade Payables', 'type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => '2320', 'name' => 'Utilities Payable', 'type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => '2400', 'name' => 'Accrued Expenses', 'type' => 'liability', 'normal_balance' => 'credit', 'is_system' => true, 'description' => 'Expenses incurred but not yet paid'],
            ['code' => '2410', 'name' => 'Accrued Salaries', 'type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => '2420', 'name' => 'Accrued Interest Payable', 'type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => '2430', 'name' => 'Accrued Taxes', 'type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => '2500', 'name' => 'Deferred Revenue', 'type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => '2600', 'name' => 'Taxes Payable', 'type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => '2610', 'name' => 'VAT Payable', 'type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => '2620', 'name' => 'PAYE Payable', 'type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => '2630', 'name' => 'Corporate Tax Payable', 'type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => '2700', 'name' => 'Other Liabilities', 'type' => 'liability', 'normal_balance' => 'credit'],

            // EQUITY (3000-3999)
            ['code' => '3100', 'name' => 'Paid-Up Capital', 'type' => 'equity', 'normal_balance' => 'credit', 'is_system' => true, 'description' => 'Initial investment by owners'],
            ['code' => '3110', 'name' => 'Share Capital', 'type' => 'equity', 'normal_balance' => 'credit'],
            ['code' => '3120', 'name' => 'Additional Paid-In Capital', 'type' => 'equity', 'normal_balance' => 'credit'],
            ['code' => '3200', 'name' => 'Retained Earnings', 'type' => 'equity', 'normal_balance' => 'credit', 'is_system' => true, 'description' => 'Accumulated profits'],
            ['code' => '3210', 'name' => 'Prior Year Retained Earnings', 'type' => 'equity', 'normal_balance' => 'credit'],
            ['code' => '3220', 'name' => 'Current Year Earnings', 'type' => 'equity', 'normal_balance' => 'credit'],
            ['code' => '3300', 'name' => 'Reserves', 'type' => 'equity', 'normal_balance' => 'credit'],
            ['code' => '3310', 'name' => 'Statutory Reserve', 'type' => 'equity', 'normal_balance' => 'credit'],
            ['code' => '3320', 'name' => 'General Reserve', 'type' => 'equity', 'normal_balance' => 'credit'],
            ['code' => '3400', 'name' => 'Grants Received', 'type' => 'equity', 'normal_balance' => 'credit'],

            // INCOME (4000-4999)
            ['code' => '4100', 'name' => 'Interest on Loans', 'type' => 'income', 'normal_balance' => 'credit', 'is_system' => true, 'allow_manual_entry' => false, 'description' => 'Interest earned on loans'],
            ['code' => '4110', 'name' => 'Interest Income - Regular Loans', 'type' => 'income', 'normal_balance' => 'credit', 'allow_manual_entry' => false],
            ['code' => '4120', 'name' => 'Interest Income - Group Loans', 'type' => 'income', 'normal_balance' => 'credit', 'allow_manual_entry' => false],
            ['code' => '4200', 'name' => 'Loan Processing Fees', 'type' => 'income', 'normal_balance' => 'credit', 'is_system' => true, 'allow_manual_entry' => false, 'description' => 'Upfront loan fees'],
            ['code' => '4210', 'name' => 'Application Fees', 'type' => 'income', 'normal_balance' => 'credit'],
            ['code' => '4220', 'name' => 'Disbursement Fees', 'type' => 'income', 'normal_balance' => 'credit'],
            ['code' => '4300', 'name' => 'Penalty Income', 'type' => 'income', 'normal_balance' => 'credit', 'is_system' => true, 'allow_manual_entry' => false, 'description' => 'Late payment penalties'],
            ['code' => '4310', 'name' => 'Late Payment Penalties', 'type' => 'income', 'normal_balance' => 'credit', 'allow_manual_entry' => false],
            ['code' => '4320', 'name' => 'Early Settlement Fees', 'type' => 'income', 'normal_balance' => 'credit'],
            ['code' => '4400', 'name' => 'Other Operating Income', 'type' => 'income', 'normal_balance' => 'credit', 'is_system' => true],
            ['code' => '4410', 'name' => 'Insurance Commission', 'type' => 'income', 'normal_balance' => 'credit'],
            ['code' => '4420', 'name' => 'SMS Fee Income', 'type' => 'income', 'normal_balance' => 'credit'],
            ['code' => '4430', 'name' => 'Account Maintenance Fees', 'type' => 'income', 'normal_balance' => 'credit'],
            ['code' => '4500', 'name' => 'Investment Income', 'type' => 'income', 'normal_balance' => 'credit'],
            ['code' => '4510', 'name' => 'Bank Interest Income', 'type' => 'income', 'normal_balance' => 'credit'],
            ['code' => '4600', 'name' => 'Other Income', 'type' => 'income', 'normal_balance' => 'credit'],
            ['code' => '4610', 'name' => 'Recovered Bad Debts', 'type' => 'income', 'normal_balance' => 'credit'],
            ['code' => '4620', 'name' => 'Miscellaneous Income', 'type' => 'income', 'normal_balance' => 'credit'],

            // EXPENSES (5000-5999)
            ['code' => '5100', 'name' => 'Staff Salaries', 'type' => 'expense', 'normal_balance' => 'debit', 'is_system' => true, 'description' => 'Employee compensation'],
            ['code' => '5110', 'name' => 'Basic Salaries', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5120', 'name' => 'Allowances', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5130', 'name' => 'Bonuses', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5140', 'name' => 'Staff Benefits', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5150', 'name' => 'Social Security Contributions', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5200', 'name' => 'Office Rent', 'type' => 'expense', 'normal_balance' => 'debit', 'is_system' => true],
            ['code' => '5210', 'name' => 'Head Office Rent', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5220', 'name' => 'Branch Office Rent', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5300', 'name' => 'Utilities', 'type' => 'expense', 'normal_balance' => 'debit', 'is_system' => true],
            ['code' => '5310', 'name' => 'Electricity', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5320', 'name' => 'Water', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5330', 'name' => 'Internet & Telephone', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5400', 'name' => 'Loan Loss Provision', 'type' => 'expense', 'normal_balance' => 'debit', 'is_system' => true, 'description' => 'Bad debt expense'],
            ['code' => '5410', 'name' => 'Provision for Loan Losses', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5420', 'name' => 'Write-offs', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5500', 'name' => 'SMS & System Costs', 'type' => 'expense', 'normal_balance' => 'debit', 'is_system' => true],
            ['code' => '5510', 'name' => 'SMS Charges', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5520', 'name' => 'Software Subscriptions', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5530', 'name' => 'IT Support & Maintenance', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5600', 'name' => 'Marketing Expenses', 'type' => 'expense', 'normal_balance' => 'debit', 'is_system' => true],
            ['code' => '5610', 'name' => 'Advertising', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5620', 'name' => 'Promotional Materials', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5630', 'name' => 'Events & Sponsorships', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5700', 'name' => 'Administrative Expenses', 'type' => 'expense', 'normal_balance' => 'debit', 'is_system' => true],
            ['code' => '5710', 'name' => 'Office Supplies', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5720', 'name' => 'Printing & Stationery', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5730', 'name' => 'Postage & Courier', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5740', 'name' => 'Bank Charges', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5800', 'name' => 'Depreciation Expense', 'type' => 'expense', 'normal_balance' => 'debit', 'is_system' => true],
            ['code' => '5810', 'name' => 'Depreciation - Equipment', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5820', 'name' => 'Depreciation - Furniture', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5830', 'name' => 'Depreciation - Computers', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5840', 'name' => 'Depreciation - Vehicles', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5900', 'name' => 'Other Operating Expenses', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5910', 'name' => 'Travel & Transport', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5920', 'name' => 'Training & Development', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5930', 'name' => 'Insurance', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5940', 'name' => 'Legal & Professional Fees', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5950', 'name' => 'Audit Fees', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5960', 'name' => 'Licenses & Permits', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5970', 'name' => 'Interest Expense', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5980', 'name' => 'Interest on Client Deposits', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5990', 'name' => 'Miscellaneous Expenses', 'type' => 'expense', 'normal_balance' => 'debit'],
        ];
    }

    public function getAccountsByType(string $type): \Illuminate\Database\Eloquent\Collection
    {
        return ChartOfAccount::active()->ofType($type)->orderBy('account_code')->get();
    }

    public function getCashAndBankAccounts(): \Illuminate\Database\Eloquent\Collection
    {
        return ChartOfAccount::active()
            ->where(function ($query) {
                $query->where('is_cash_account', true)
                      ->orWhere('is_bank_account', true);
            })
            ->orderBy('account_code')
            ->get();
    }

    public function getAccountTree(): array
    {
        $accounts = ChartOfAccount::active()
            ->orderBy('account_code')
            ->get()
            ->groupBy('account_type');

        $tree = [];
        foreach (ChartOfAccount::getAccountTypes() as $type => $label) {
            $tree[$type] = [
                'label' => $label,
                'accounts' => $accounts[$type] ?? collect(),
            ];
        }

        return $tree;
    }
}
