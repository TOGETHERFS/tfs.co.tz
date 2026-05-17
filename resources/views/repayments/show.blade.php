@extends('layouts.app')

@section('title', 'Repayment Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Repayment Details</h3>
                    <div class="card-tools">
                        <a href="{{ route('repayments.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Repayments
                        </a>
                        @if(isset($repayment))
                            <a href="{{ route('repayments.edit', $repayment->id) }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @if(isset($repayment))
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Repayment Information</h5>
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Repayment ID:</strong></td>
                                        <td>{{ $repayment->id }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Amount:</strong></td>
                                        <td>{{ number_format($repayment->amount, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Payment Date:</strong></td>
                                        <td>{{ $repayment->payment_date ? \Carbon\Carbon::parse($repayment->payment_date)->format('d M Y') : 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Payment Method:</strong></td>
                                        <td>{{ ucfirst(str_replace('_', ' ', $repayment->payment_method ?? 'N/A')) }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Reference Number:</strong></td>
                                        <td>{{ $repayment->reference_number ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status:</strong></td>
                                        <td>
                                            @if($repayment->status == 'completed')
                                                <span class="badge badge-success">Completed</span>
                                            @elseif($repayment->status == 'pending')
                                                <span class="badge badge-warning">Pending</span>
                                            @elseif($repayment->status == 'failed')
                                                <span class="badge badge-danger">Failed</span>
                                            @else
                                                <span class="badge badge-secondary">{{ ucfirst($repayment->status ?? 'Unknown') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Created At:</strong></td>
                                        <td>{{ $repayment->created_at ? $repayment->created_at->format('d M Y H:i') : 'N/A' }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5>Loan Information</h5>
                                @if($repayment->loan)
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Loan ID:</strong></td>
                                            <td>
                                                <a href="{{ route('loans.show', $repayment->loan->id) }}">
                                                    {{ $repayment->loan->id }}
                                                </a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Client:</strong></td>
                                            <td>{{ $repayment->loan->client->name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Principal Amount:</strong></td>
                                            <td>{{ number_format($repayment->loan->principal_amount, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Outstanding Balance:</strong></td>
                                            <td>{{ number_format($repayment->loan->outstanding_balance, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Loan Status:</strong></td>
                                            <td>
                                                @if($repayment->loan->status == 'active')
                                                    <span class="badge badge-success">Active</span>
                                                @elseif($repayment->loan->status == 'completed')
                                                    <span class="badge badge-info">Completed</span>
                                                @elseif($repayment->loan->status == 'defaulted')
                                                    <span class="badge badge-danger">Defaulted</span>
                                                @else
                                                    <span class="badge badge-secondary">{{ ucfirst($repayment->loan->status ?? 'Unknown') }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                @else
                                    <p class="text-muted">No loan information available</p>
                                @endif
                            </div>
                        </div>

                        @if($repayment->notes)
                            <div class="row mt-3">
                                <div class="col-12">
                                    <h5>Notes</h5>
                                    <div class="card">
                                        <div class="card-body">
                                            {{ $repayment->notes }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="row mt-3">
                            <div class="col-12">
                                <h5>Actions</h5>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('repayments.edit', $repayment->id) }}" class="btn btn-primary">
                                        <i class="fas fa-edit"></i> Edit Repayment
                                    </a>
                                    @if($repayment->loan)
                                        <a href="{{ route('loans.show', $repayment->loan->id) }}" class="btn btn-info">
                                            <i class="fas fa-eye"></i> View Loan
                                        </a>
                                    @endif
                                    <button type="button" class="btn btn-success" onclick="window.print()">
                                        <i class="fas fa-print"></i> Print Receipt
                                    </button>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <h5>Repayment Not Found</h5>
                            <p>The requested repayment could not be found.</p>
                            <a href="{{ route('repayments.index') }}" class="btn btn-primary">Back to Repayments</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection