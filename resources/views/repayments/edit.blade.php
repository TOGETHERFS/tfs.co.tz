@extends('layouts.app')

@section('title', 'Edit Repayment')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit Repayment</h3>
                    <div class="card-tools">
                        <a href="{{ route('repayments.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Repayments
                        </a>
                        @if(isset($repayment))
                            <a href="{{ route('repayments.show', $repayment->id) }}" class="btn btn-info btn-sm">
                                <i class="fas fa-eye"></i> View
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @if(isset($repayment))
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h5>Loan Information</h5>
                                @if($repayment->loan)
                                    <p><strong>Loan ID:</strong> {{ $repayment->loan->id }}</p>
                                    <p><strong>Client:</strong> {{ $repayment->loan->client->name ?? 'N/A' }}</p>
                                    <p><strong>Principal Amount:</strong> {{ number_format($repayment->loan->principal_amount, 2) }}</p>
                                    <p><strong>Outstanding Balance:</strong> {{ number_format($repayment->loan->outstanding_balance, 2) }}</p>
                                @else
                                    <p class="text-muted">No loan information available</p>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <h5>Current Repayment Details</h5>
                                <p><strong>Repayment ID:</strong> {{ $repayment->id }}</p>
                                <p><strong>Current Amount:</strong> {{ number_format($repayment->amount, 2) }}</p>
                                <p><strong>Current Status:</strong> 
                                    @if($repayment->status == 'completed')
                                        <span class="badge badge-success">Completed</span>
                                    @elseif($repayment->status == 'pending')
                                        <span class="badge badge-warning">Pending</span>
                                    @elseif($repayment->status == 'failed')
                                        <span class="badge badge-danger">Failed</span>
                                    @else
                                        <span class="badge badge-secondary">{{ ucfirst($repayment->status ?? 'Unknown') }}</span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        <hr>

                        <form action="{{ route('repayments.update', $repayment->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="amount">Repayment Amount</label>
                                        <input type="number" step="0.01" class="form-control @error('amount') is-invalid @enderror" 
                                               id="amount" name="amount" value="{{ old('amount', $repayment->amount) }}" required>
                                        @error('amount')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="payment_date">Payment Date</label>
                                        <input type="date" class="form-control @error('payment_date') is-invalid @enderror" 
                                               id="payment_date" name="payment_date" 
                                               value="{{ old('payment_date', $repayment->payment_date ? \Carbon\Carbon::parse($repayment->payment_date)->format('Y-m-d') : '') }}" required>
                                        @error('payment_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="payment_method">Payment Method</label>
                                        <select class="form-control @error('payment_method') is-invalid @enderror" 
                                                id="payment_method" name="payment_method" required>
                                            <option value="">Select Payment Method</option>
                                            <option value="cash" {{ old('payment_method', $repayment->payment_method) == 'cash' ? 'selected' : '' }}>Cash</option>
                                            <option value="bank_transfer" {{ old('payment_method', $repayment->payment_method) == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                            <option value="mobile_money" {{ old('payment_method', $repayment->payment_method) == 'mobile_money' ? 'selected' : '' }}>Mobile Money</option>
                                            <option value="cheque" {{ old('payment_method', $repayment->payment_method) == 'cheque' ? 'selected' : '' }}>Cheque</option>
                                        </select>
                                        @error('payment_method')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="reference_number">Reference Number</label>
                                        <input type="text" class="form-control @error('reference_number') is-invalid @enderror" 
                                               id="reference_number" name="reference_number" 
                                               value="{{ old('reference_number', $repayment->reference_number) }}">
                                        @error('reference_number')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="status">Status</label>
                                        <select class="form-control @error('status') is-invalid @enderror" 
                                                id="status" name="status" required>
                                            <option value="pending" {{ old('status', $repayment->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="completed" {{ old('status', $repayment->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                                            <option value="failed" {{ old('status', $repayment->status) == 'failed' ? 'selected' : '' }}>Failed</option>
                                        </select>
                                        @error('status')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="notes">Notes</label>
                                <textarea class="form-control @error('notes') is-invalid @enderror" 
                                          id="notes" name="notes" rows="3">{{ old('notes', $repayment->notes) }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Repayment
                                </button>
                                <a href="{{ route('repayments.show', $repayment->id) }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
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