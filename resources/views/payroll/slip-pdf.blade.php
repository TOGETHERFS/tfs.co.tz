<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('messages.salary_slip') }} - {{ $salary->user->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; background: #fff; }
        .header { background: #059669; color: white; padding: 20px 30px; margin-bottom: 20px; }
        .header h1 { font-size: 20px; font-weight: bold; }
        .header p { font-size: 11px; opacity: 0.85; margin-top: 3px; }
        .slip-title { text-align: center; font-size: 16px; font-weight: bold; color: #059669; padding: 10px 30px 5px; border-bottom: 2px solid #059669; margin: 0 30px 20px; }
        .section { margin: 0 30px 15px; }
        .section-title { font-size: 11px; font-weight: bold; text-transform: uppercase; color: #6b7280; letter-spacing: 0.05em; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; margin-bottom: 8px; }
        .info-grid { display: table; width: 100%; }
        .info-row { display: table-row; }
        .info-label { display: table-cell; width: 40%; color: #6b7280; padding: 3px 0; }
        .info-value { display: table-cell; font-weight: 600; padding: 3px 0; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        table th { background: #f3f4f6; text-align: left; padding: 7px 10px; font-size: 11px; color: #374151; font-weight: 600; }
        table td { padding: 6px 10px; border-bottom: 1px solid #f3f4f6; }
        table td.right { text-align: right; }
        .summary { margin: 20px 30px; background: #ecfdf5; border: 1px solid #6ee7b7; border-radius: 6px; padding: 15px; }
        .summary-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 12px; }
        .summary-row.total { border-top: 2px solid #6ee7b7; padding-top: 8px; margin-top: 4px; font-size: 15px; font-weight: bold; color: #059669; }
        .footer { margin: 25px 30px 0; border-top: 1px solid #e5e7eb; padding-top: 15px; display: flex; justify-content: space-between; font-size: 10px; color: #9ca3af; }
        .signature-box { margin: 30px 30px 0; display: flex; justify-content: space-between; }
        .sig { text-align: center; width: 45%; }
        .sig-line { border-top: 1px solid #374151; margin-top: 40px; padding-top: 4px; font-size: 11px; color: #6b7280; }
    </style>
</head>
<body>

    <div class="header">
        <h1>{{ __('messages.salary_slip') }}</h1>
        <p>{{ __('messages.generated_on') }}: {{ now()->format('d F Y') }}</p>
    </div>

    <div class="slip-title">{{ __('messages.salary_slip') }} — {{ \Carbon\Carbon::createFromFormat('Y-m', $salary->month)->format('F Y') }}</div>

    {{-- Staff Details --}}
    <div class="section">
        <div class="section-title">{{ __('messages.employee_details') }}</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">{{ __('messages.staff_name') }}</div>
                <div class="info-value">{{ $salary->user->name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">{{ __('messages.position') }}</div>
                <div class="info-value">{{ $salary->user->position ?? $salary->user->role }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">{{ __('messages.month') }}</div>
                <div class="info-value">{{ \Carbon\Carbon::createFromFormat('Y-m', $salary->month)->format('F Y') }}</div>
            </div>
            @if($salary->payment_date)
            <div class="info-row">
                <div class="info-label">{{ __('messages.payment_date') }}</div>
                <div class="info-value">{{ $salary->payment_date->format('d F Y') }}</div>
            </div>
            @endif
        </div>
    </div>

    {{-- Earnings --}}
    <div class="section">
        <div class="section-title">{{ __('messages.earnings') }}</div>
        <table>
            <thead>
                <tr>
                    <th>{{ __('messages.description') }}</th>
                    <th style="text-align:right">{{ __('messages.amount') }} (TZS)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ __('messages.basic_salary') }}</td>
                    <td class="right">{{ number_format($salary->basic_salary, 2) }}</td>
                </tr>
                @if($salary->allowances_breakdown)
                    @foreach($salary->allowances_breakdown as $item)
                    <tr>
                        <td>{{ $item['name'] }}</td>
                        <td class="right">{{ number_format($item['amount'], 2) }}</td>
                    </tr>
                    @endforeach
                @endif
                <tr style="font-weight:bold; background:#f9fafb;">
                    <td>{{ __('messages.total_earnings') }}</td>
                    <td class="right">{{ number_format($salary->basic_salary + $salary->allowances, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Employee Deductions (PAYE, NSSF Employee) --}}
    @php
        $grossIncome   = (float)$salary->basic_salary + (float)$salary->allowances;
        $deductionRows = $salary->deductions_breakdown ?? [];
        if (empty($deductionRows) && $grossIncome > 0) {
            $deductionRows = \App\Services\TanzaniaTaxService::deductionsBreakdown($grossIncome);
        }
    @endphp
    <div class="section">
        <div class="section-title">{{ __('messages.deductions') }} (Employee Statutory Deductions)</div>
        <table>
            <thead>
                <tr>
                    <th>{{ __('messages.description') }}</th>
                    <th style="text-align:right">{{ __('messages.amount') }} (TZS)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($deductionRows as $item)
                <tr>
                    <td>{{ $item['name'] }}</td>
                    <td class="right">{{ number_format($item['amount'], 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="2" style="color:#6b7280; text-align:center;">No deductions applicable (salary below tax threshold)</td>
                </tr>
                @endforelse
                <tr style="font-weight:bold; background:#fef2f2;">
                    <td>{{ __('messages.total_deductions') }}</td>
                    <td class="right" style="color:#dc2626;">{{ number_format($salary->deductions, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Employer Contributions (NSSF Employer, SDL, WCF) — shown for transparency --}}
    @php
        $grossIncome = (float)$salary->basic_salary + (float)$salary->allowances;
        $employerRows = $salary->employer_contributions ?? [];
        if (empty($employerRows) && $grossIncome > 0) {
            $employerRows = \App\Services\TanzaniaTaxService::employerContributionsBreakdown($grossIncome);
        }
        $totalEmployer = collect($employerRows)->sum('amount');
    @endphp
    @if(!empty($employerRows))
    <div class="section">
        <div class="section-title">Employer Statutory Contributions (Not deducted from employee pay)</div>
        <table>
            <thead>
                <tr>
                    <th>{{ __('messages.description') }}</th>
                    <th style="text-align:right">{{ __('messages.amount') }} (TZS)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($employerRows as $item)
                <tr>
                    <td style="color:#374151;">{{ $item['name'] }}</td>
                    <td class="right" style="color:#374151;">{{ number_format($item['amount'], 2) }}</td>
                </tr>
                @endforeach
                <tr style="font-weight:bold; background:#eff6ff;">
                    <td>Total Employer Contributions</td>
                    <td class="right" style="color:#1d4ed8;">{{ number_format($totalEmployer, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

    {{-- Net Salary Summary --}}
    <div class="summary">
        <div class="summary-row">
            <span>{{ __('messages.total_earnings') }} (Gross)</span>
            <span>TZS {{ number_format((float)$salary->basic_salary + (float)$salary->allowances, 2) }}</span>
        </div>
        <div class="summary-row">
            <span>PAYE (Income Tax)</span>
            @php $paye = collect($deductionRows ?? [])->where('name','PAYE')->sum('amount'); @endphp
            <span style="color:#dc2626;">- TZS {{ number_format($paye, 2) }}</span>
        </div>
        <div class="summary-row">
            <span>NSSF (Employee 10%)</span>
            @php $nssfEmp = collect($deductionRows ?? [])->where('name','NSSF (Employee 10%)')->sum('amount'); @endphp
            <span style="color:#dc2626;">- TZS {{ number_format($nssfEmp, 2) }}</span>
        </div>
        <div class="summary-row">
            <span>{{ __('messages.total_deductions') }}</span>
            <span style="color:#dc2626;">- TZS {{ number_format($salary->deductions, 2) }}</span>
        </div>
        <div class="summary-row total">
            <span>{{ __('messages.net_salary') }} (Take Home)</span>
            <span>TZS {{ number_format($salary->net_salary, 2) }}</span>
        </div>
    </div>

    {{-- Statutory Notice --}}
    <div style="margin: 8px 30px 15px; font-size:9px; color:#6b7280; border:1px solid #e5e7eb; border-radius:4px; padding:8px;">
        <strong>Statutory Deductions Notice:</strong>
        PAYE calculated per TRA progressive tax brackets (Income Tax Act Cap.332) |
        NSSF Employee 10% + Employer 10% (NSSF Act 2018) |
        SDL Employer 4.5% (Vocational Education &amp; Training Act) |
        WCF Employer 0.5% (Workers Compensation Fund Act)
    </div>

    {{-- Signatures --}}
    <div class="signature-box">
        <div class="sig">
            <div class="sig-line">{{ __('messages.employee_signature') }}</div>
        </div>
        <div class="sig">
            <div class="sig-line">{{ __('messages.authorised_signature') }}</div>
        </div>
    </div>

    <div class="footer">
        <span>{{ __('messages.salary_slip') }} — {{ now()->format('d/m/Y H:i') }}</span>
        <span>{{ __('messages.confidential') }}</span>
    </div>

</body>
</html>
