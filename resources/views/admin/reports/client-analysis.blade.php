@extends('layouts.admin')

@section('title', 'Client Analysis Report')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Client Analysis Report</h3>
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
                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3>{{ number_format($data['total_clients']) }}</h3>
                                    <p>Total Clients</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3>{{ number_format($data['active_clients']) }}</h3>
                                    <p>Active Clients</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-user-check"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3>{{ number_format($data['clients_with_loans']) }}</h3>
                                    <p>Clients with Loans</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-handshake"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-secondary">
                                <div class="inner">
                                    <h3>{{ number_format($data['total_clients'] - $data['clients_with_loans']) }}</h3>
                                    <p>Prospects</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-user-plus"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Client Analysis Details -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Client Demographics</h3>
                                </div>
                                <div class="card-body">
                                    @if(isset($data['client_demographics']))
                                        <div class="row">
                                            <div class="col-12">
                                                <h5>Gender Distribution</h5>
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>Gender</th>
                                                            <th>Count</th>
                                                            <th>Percentage</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($data['client_demographics']['gender'] ?? [] as $gender)
                                                            <tr>
                                                                <td>{{ ucfirst($gender->gender ?? 'Unknown') }}</td>
                                                                <td>{{ $gender->count }}</td>
                                                                <td>{{ number_format(($gender->count / $data['total_clients']) * 100, 1) }}%</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        
                                        <div class="row mt-3">
                                            <div class="col-12">
                                                <h5>Age Groups</h5>
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>Age Group</th>
                                                            <th>Count</th>
                                                            <th>Percentage</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($data['client_demographics']['age_groups'] ?? [] as $group)
                                                            <tr>
                                                                <td>{{ $group->age_group }}</td>
                                                                <td>{{ $group->count }}</td>
                                                                <td>{{ number_format(($group->count / $data['total_clients']) * 100, 1) }}%</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    @else
                                        <p class="text-muted">No demographic data available.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Client Status Analysis</h3>
                                </div>
                                <div class="card-body">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-green"><i class="fas fa-percentage"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Active Rate</span>
                                            <span class="info-box-number">{{ number_format(($data['active_clients'] / max($data['total_clients'], 1)) * 100, 1) }}%</span>
                                        </div>
                                    </div>
                                    
                                    <div class="info-box">
                                        <span class="info-box-icon bg-blue"><i class="fas fa-chart-pie"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Loan Penetration</span>
                                            <span class="info-box-number">{{ number_format(($data['clients_with_loans'] / max($data['total_clients'], 1)) * 100, 1) }}%</span>
                                        </div>
                                    </div>
                                    
                                    <div class="info-box">
                                        <span class="info-box-icon bg-yellow"><i class="fas fa-user-clock"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Prospect Rate</span>
                                            <span class="info-box-number">{{ number_format((($data['total_clients'] - $data['clients_with_loans']) / max($data['total_clients'], 1)) * 100, 1) }}%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Analysis -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Client Performance Metrics</h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="description-block border-right">
                                                <span class="description-percentage text-success"><i class="fas fa-caret-up"></i> {{ number_format(($data['active_clients'] / max($data['total_clients'], 1)) * 100, 1) }}%</span>
                                                <h5 class="description-header">{{ number_format($data['active_clients']) }}</h5>
                                                <span class="description-text">ACTIVE CLIENTS</span>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="description-block border-right">
                                                <span class="description-percentage text-warning"><i class="fas fa-caret-left"></i> {{ number_format(($data['clients_with_loans'] / max($data['total_clients'], 1)) * 100, 1) }}%</span>
                                                <h5 class="description-header">{{ number_format($data['clients_with_loans']) }}</h5>
                                                <span class="description-text">WITH LOANS</span>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="description-block border-right">
                                                <span class="description-percentage text-info"><i class="fas fa-caret-up"></i> {{ number_format((($data['total_clients'] - $data['clients_with_loans']) / max($data['total_clients'], 1)) * 100, 1) }}%</span>
                                                <h5 class="description-header">{{ number_format($data['total_clients'] - $data['clients_with_loans']) }}</h5>
                                                <span class="description-text">PROSPECTS</span>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="description-block">
                                                <span class="description-percentage text-secondary"><i class="fas fa-caret-down"></i> {{ number_format((($data['total_clients'] - $data['active_clients']) / max($data['total_clients'], 1)) * 100, 1) }}%</span>
                                                <h5 class="description-header">{{ number_format($data['total_clients'] - $data['active_clients']) }}</h5>
                                                <span class="description-text">INACTIVE</span>
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
</div>
@endsection