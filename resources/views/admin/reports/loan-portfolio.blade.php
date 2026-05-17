@extends('layouts.admin')

@section('title', 'Loan Portfolio Report')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Loan Portfolio Report</h3>
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
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3>{{ number_format($data['total_loans']) }}</h3>
                                    <p>Total Loans</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3>TZS {{ number_format($data['total_disbursed'], 2) }}</h3>
                                    <p>Total Disbursed</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3>{{ number_format($data['active_loans']) }}</h3>
                                    <p>Active Loans</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3>TZS {{ number_format($data['total_outstanding'], 2) }}</h3>
                                    <p>Outstanding Balance</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Portfolio Details -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Loans by Status</h3>
                                </div>
                                <div class="card-body">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Status</th>
                                                <th>Count</th>
                                                <th>Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @if(isset($data['loans_by_status']))
                                                @foreach($data['loans_by_status'] as $status)
                                                    <tr>
                                                        <td>{{ ucfirst($status->status) }}</td>
                                                        <td>{{ $status->count }}</td>
                                                        <td>{{ number_format(($status->count / $data['total_loans']) * 100, 1) }}%</td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Portfolio Risk Analysis</h3>
                                </div>
                                <div class="card-body">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-red"><i class="fas fa-exclamation-triangle"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Portfolio at Risk</span>
                                            <span class="info-box-number">{{ number_format($data['portfolio_at_risk'] ?? 0, 2) }}%</span>
                                        </div>
                                    </div>
                                    <div class="info-box">
                                        <span class="info-box-icon bg-yellow"><i class="fas fa-clock"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Overdue Loans</span>
                                            <span class="info-box-number">{{ number_format($data['overdue_loans']) }}</span>
                                        </div>
                                    </div>
                                    <div class="info-box">
                                        <span class="info-box-icon bg-green"><i class="fas fa-check"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Completed Loans</span>
                                            <span class="info-box-number">{{ number_format($data['completed_loans']) }}</span>
                                        </div>
                                    </div>
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