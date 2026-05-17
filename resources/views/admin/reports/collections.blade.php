@extends('layouts.admin')

@section('title', 'Collections Report')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Collections Report</h3>
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
                                    <h3>TZS {{ number_format($data['total_collected'], 2) }}</h3>
                                    <p>Total Collections</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3>{{ count($data['daily_collections'] ?? []) }}</h3>
                                    <p>Collection Days</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-calendar"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3>TZS {{ number_format(($data['total_collected'] ?? 0) / max(count($data['daily_collections'] ?? []), 1), 2) }}</h3>
                                    <p>Daily Average</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Collections Details -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Collections by Payment Method</h3>
                                </div>
                                <div class="card-body">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Payment Method</th>
                                                <th>Amount</th>
                                                <th>Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @if(isset($data['collections_by_method']))
                                                @foreach($data['collections_by_method'] as $method)
                                                    <tr>
                                                        <td>{{ ucfirst($method->payment_method ?? 'Unknown') }}</td>
                                                        <td>TZS {{ number_format($method->total, 2) }}</td>
                                                        <td>{{ number_format(($method->total / ($data['total_collected'] ?: 1)) * 100, 1) }}%</td>
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
                                    <h3 class="card-title">Daily Collections Trend</h3>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Amount</th>
                                                    <th>Transactions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @if(isset($data['daily_collections']))
                                                    @foreach($data['daily_collections'] as $daily)
                                                        <tr>
                                                            <td>{{ \Carbon\Carbon::parse($daily->date)->format('M d, Y') }}</td>
                                                            <td>TZS {{ number_format($daily->total, 2) }}</td>
                                                            <td>{{ $daily->count }}</td>
                                                        </tr>
                                                    @endforeach
                                                @endif
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
</div>
@endsection