@extends('layouts.admin')

@section('title', 'Profit & Loss Report')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Profit & Loss Report</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
                            <i class="fas fa-print"></i> Print Report
                        </button>
                        <button type="button" class="btn btn-success btn-sm" onclick="window.print()">
                            <i class="fas fa-download"></i> Export PDF
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Date Range Filter -->
                    <form method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="date_from">From Date:</label>
                                <input type="date" name="date_from" id="date_from" class="form-control" value="{{ $dateFrom }}">
                            </div>
                            <div class="col-md-3">
                                <label for="date_to">To Date:</label>
                                <input type="date" name="date_to" id="date_to" class="form-control" value="{{ $dateTo }}">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </div>
                        </div>
                    </form>

                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-lg-4 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3>TZS {{ number_format($data['income']['total'] ?? 0, 2) }}</h3>
                                    <p>Total Income</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-arrow-up"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-6">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3>TZS {{ number_format($data['expenses']['total'] ?? 0, 2) }}</h3>
                                    <p>Total Expenses</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-arrow-down"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-6">
                            <div class="small-box {{ ($data['net_profit'] ?? 0) >= 0 ? 'bg-success' : 'bg-danger' }}">
                                <div class="inner">
                                    <h3>TZS {{ number_format($data['net_profit'] ?? 0, 2) }}</h3>
                                    <p>Net {{ ($data['net_profit'] ?? 0) >= 0 ? 'Profit' : 'Loss' }}</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-{{ ($data['net_profit'] ?? 0) >= 0 ? 'chart-line' : 'chart-line-down' }}"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detailed P&L Statement -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Detailed Profit & Loss Statement</h3>
                                </div>
                                <div class="card-body">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Category</th>
                                                <th class="text-right">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Income Section -->
                                            <tr class="table-success">
                                                <td><strong>INCOME</strong></td>
                                                <td class="text-right"><strong>TZS {{ number_format($data['income']['total'] ?? 0, 2) }}</strong></td>
                                            </tr>
                                            @if(isset($data['income']['breakdown']))
                                                @foreach($data['income']['breakdown'] as $item)
                                                    <tr>
                                                        <td class="pl-4">{{ $item['category'] ?? 'Unknown' }}</td>
                                                        <td class="text-right">TZS {{ number_format($item['amount'] ?? 0, 2) }}</td>
                                                    </tr>
                                                @endforeach
                                            @else
                                                <tr>
                                                    <td class="pl-4">Interest Income</td>
                                                    <td class="text-right">TZS {{ number_format($data['income']['interest'] ?? 0, 2) }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="pl-4">Fee Income</td>
                                                    <td class="text-right">TZS {{ number_format($data['income']['fees'] ?? 0, 2) }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="pl-4">Other Income</td>
                                                    <td class="text-right">TZS {{ number_format($data['income']['other'] ?? 0, 2) }}</td>
                                                </tr>
                                            @endif
                                            
                                            <!-- Expenses Section -->
                                            <tr class="table-danger">
                                                <td><strong>EXPENSES</strong></td>
                                                <td class="text-right"><strong>TZS {{ number_format($data['expenses']['total'] ?? 0, 2) }}</strong></td>
                                            </tr>
                                            @if(isset($data['expenses']['breakdown']))
                                                @foreach($data['expenses']['breakdown'] as $item)
                                                    <tr>
                                                        <td class="pl-4">{{ $item['category'] ?? 'Unknown' }}</td>
                                                        <td class="text-right">TZS {{ number_format($item['amount'] ?? 0, 2) }}</td>
                                                    </tr>
                                                @endforeach
                                            @else
                                                <tr>
                                                    <td class="pl-4">Operating Expenses</td>
                                                    <td class="text-right">TZS {{ number_format($data['expenses']['operating'] ?? 0, 2) }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="pl-4">Administrative Expenses</td>
                                                    <td class="text-right">TZS {{ number_format($data['expenses']['administrative'] ?? 0, 2) }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="pl-4">Loan Loss Provisions</td>
                                                    <td class="text-right">TZS {{ number_format($data['expenses']['provisions'] ?? 0, 2) }}</td>
                                                </tr>
                                            @endif
                                            
                                            <!-- Net Profit/Loss -->
                                            <tr class="table-{{ ($data['net_profit'] ?? 0) >= 0 ? 'success' : 'danger' }}">
                                                <td><strong>NET {{ ($data['net_profit'] ?? 0) >= 0 ? 'PROFIT' : 'LOSS' }}</strong></td>
                                                <td class="text-right"><strong>TZS {{ number_format($data['net_profit'] ?? 0, 2) }}</strong></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection