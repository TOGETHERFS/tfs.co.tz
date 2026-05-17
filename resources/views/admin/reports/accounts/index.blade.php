@extends('layouts.admin')

@section('title', 'Accounts Reports')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Accounts Reports</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- General Ledger -->
                        <div class="col-lg-4 col-md-6">
                            <div class="card card-primary card-outline">
                                <div class="card-body box-profile">
                                    <div class="text-center">
                                        <i class="fas fa-book fa-3x text-primary mb-3"></i>
                                    </div>
                                    <h3 class="profile-username text-center">General Ledger</h3>
                                    <p class="text-muted text-center">Complete record of all financial transactions</p>
                                    <a href="{{ route('admin.reports.general-ledger') }}" class="btn btn-primary btn-block">
                                        <i class="fas fa-eye"></i> View Report
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Trial Balance -->
                        <div class="col-lg-4 col-md-6">
                            <div class="card card-success card-outline">
                                <div class="card-body box-profile">
                                    <div class="text-center">
                                        <i class="fas fa-balance-scale fa-3x text-success mb-3"></i>
                                    </div>
                                    <h3 class="profile-username text-center">Trial Balance</h3>
                                    <p class="text-muted text-center">Summary of all account balances</p>
                                    <a href="{{ route('admin.reports.trial-balance') }}" class="btn btn-success btn-block">
                                        <i class="fas fa-eye"></i> View Report
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Cash Book -->
                        <div class="col-lg-4 col-md-6">
                            <div class="card card-info card-outline">
                                <div class="card-body box-profile">
                                    <div class="text-center">
                                        <i class="fas fa-money-bill-wave fa-3x text-info mb-3"></i>
                                    </div>
                                    <h3 class="profile-username text-center">Cash Book</h3>
                                    <p class="text-muted text-center">Record of all cash transactions</p>
                                    <a href="{{ route('admin.reports.cashbook') }}" class="btn btn-info btn-block">
                                        <i class="fas fa-eye"></i> View Report
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Bank Book -->
                        <div class="col-lg-4 col-md-6">
                            <div class="card card-warning card-outline">
                                <div class="card-body box-profile">
                                    <div class="text-center">
                                        <i class="fas fa-university fa-3x text-warning mb-3"></i>
                                    </div>
                                    <h3 class="profile-username text-center">Bank Book</h3>
                                    <p class="text-muted text-center">Record of all bank transactions</p>
                                    <a href="{{ route('admin.reports.bankbook') }}" class="btn btn-warning btn-block">
                                        <i class="fas fa-eye"></i> View Report
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Client Ledger -->
                        <div class="col-lg-4 col-md-6">
                            <div class="card card-danger card-outline">
                                <div class="card-body box-profile">
                                    <div class="text-center">
                                        <i class="fas fa-users fa-3x text-danger mb-3"></i>
                                    </div>
                                    <h3 class="profile-username text-center">Client Ledger</h3>
                                    <p class="text-muted text-center">Individual client account statements</p>
                                    <a href="{{ route('admin.reports.client-ledger') }}" class="btn btn-danger btn-block">
                                        <i class="fas fa-eye"></i> View Report
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Income Categories -->
                        <div class="col-lg-4 col-md-6">
                            <div class="card card-secondary card-outline">
                                <div class="card-body box-profile">
                                    <div class="text-center">
                                        <i class="fas fa-arrow-up fa-3x text-secondary mb-3"></i>
                                    </div>
                                    <h3 class="profile-username text-center">Income Categories</h3>
                                    <p class="text-muted text-center">Breakdown of income by categories</p>
                                    <a href="{{ route('admin.reports.income-categories') }}" class="btn btn-secondary btn-block">
                                        <i class="fas fa-eye"></i> View Report
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Expenditure Categories -->
                        <div class="col-lg-4 col-md-6">
                            <div class="card card-dark card-outline">
                                <div class="card-body box-profile">
                                    <div class="text-center">
                                        <i class="fas fa-arrow-down fa-3x text-dark mb-3"></i>
                                    </div>
                                    <h3 class="profile-username text-center">Expenditure Categories</h3>
                                    <p class="text-muted text-center">Breakdown of expenses by categories</p>
                                    <a href="{{ route('admin.reports.expenditure-categories') }}" class="btn btn-dark btn-block">
                                        <i class="fas fa-eye"></i> View Report
                                    </a>
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