@extends('layouts.admin')

@section('title', 'Edit SMS Wallet')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit SMS Wallet</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.sms-wallets.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to SMS Wallets
                        </a>
                        @if(isset($smsWallet))
                            <a href="{{ route('admin.sms-wallets.show', $smsWallet->id) }}" class="btn btn-info btn-sm">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                        @endif
                    </div>
                </div>

                @if(isset($smsWallet))
                    <form action="{{ route('admin.sms-wallets.update', $smsWallet->id) }}" method="POST" id="editWalletForm">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <h6><i class="fas fa-exclamation-triangle"></i> Please fix the following errors:</h6>
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="tenant_id">Tenant <span class="text-danger">*</span></label>
                                        <select class="form-control @error('tenant_id') is-invalid @enderror" 
                                                id="tenant_id" name="tenant_id" required>
                                            <option value="">Select Tenant</option>
                                            @if(isset($tenants))
                                                @foreach($tenants as $tenant)
                                                    <option value="{{ $tenant->id }}" 
                                                            {{ old('tenant_id', $smsWallet->tenant_id) == $tenant->id ? 'selected' : '' }}>
                                                        {{ $tenant->name }} ({{ $tenant->email }})
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                        @error('tenant_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">Current tenant for this SMS wallet</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="current_balance">Current Balance</label>
                                        <div class="input-group">
                                            <input type="number" 
                                                   class="form-control" 
                                                   id="current_balance" 
                                                   value="{{ $smsWallet->balance }}" 
                                                   readonly>
                                            <div class="input-group-append">
                                                <span class="input-group-text">SMS Credits</span>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">Use the "Adjust Balance" section below to modify the balance</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="currency">Currency</label>
                                        <select class="form-control @error('currency') is-invalid @enderror" 
                                                id="currency" name="currency">
                                            <option value="TZS" {{ old('currency', $smsWallet->currency ?? 'TZS') == 'TZS' ? 'selected' : '' }}>TZS - Tanzanian Shilling</option>
                                            <option value="USD" {{ old('currency', $smsWallet->currency) == 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
                                            <option value="EUR" {{ old('currency', $smsWallet->currency) == 'EUR' ? 'selected' : '' }}>EUR - Euro</option>
                                            <option value="KES" {{ old('currency', $smsWallet->currency) == 'KES' ? 'selected' : '' }}>KES - Kenyan Shilling</option>
                                            <option value="UGX" {{ old('currency', $smsWallet->currency) == 'UGX' ? 'selected' : '' }}>UGX - Ugandan Shilling</option>
                                        </select>
                                        @error('currency')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="low_balance_threshold">Low Balance Threshold</label>
                                        <input type="number" 
                                               class="form-control @error('low_balance_threshold') is-invalid @enderror" 
                                               id="low_balance_threshold" 
                                               name="low_balance_threshold" 
                                               value="{{ old('low_balance_threshold', $smsWallet->low_balance_threshold ?? 10) }}" 
                                               min="0" 
                                               step="1">
                                        @error('low_balance_threshold')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">Alert when balance falls below this amount</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="daily_limit">Daily SMS Limit</label>
                                        <input type="number" 
                                               class="form-control @error('daily_limit') is-invalid @enderror" 
                                               id="daily_limit" 
                                               name="daily_limit" 
                                               value="{{ old('daily_limit', $smsWallet->daily_limit) }}" 
                                               min="0" 
                                               step="1">
                                        @error('daily_limit')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">Maximum SMS per day (leave empty for unlimited)</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="monthly_limit">Monthly SMS Limit</label>
                                        <input type="number" 
                                               class="form-control @error('monthly_limit') is-invalid @enderror" 
                                               id="monthly_limit" 
                                               name="monthly_limit" 
                                               value="{{ old('monthly_limit', $smsWallet->monthly_limit) }}" 
                                               min="0" 
                                               step="1">
                                        @error('monthly_limit')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">Maximum SMS per month (leave empty for unlimited)</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="description">Description</label>
                                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                                  id="description" 
                                                  name="description" 
                                                  rows="3" 
                                                  placeholder="Optional description for this SMS wallet">{{ old('description', $smsWallet->description) }}</textarea>
                                        @error('description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <h5>Wallet Settings</h5>
                                    <div class="form-group">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" 
                                                   class="custom-control-input" 
                                                   id="is_active" 
                                                   name="is_active" 
                                                   value="1" 
                                                   {{ old('is_active', $smsWallet->is_active) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="is_active">
                                                Active Wallet
                                            </label>
                                            <small class="form-text text-muted">Uncheck to deactivate this wallet</small>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" 
                                                   class="custom-control-input" 
                                                   id="auto_topup_enabled" 
                                                   name="auto_topup_enabled" 
                                                   value="1" 
                                                   {{ old('auto_topup_enabled', $smsWallet->auto_topup_enabled) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="auto_topup_enabled">
                                                Enable Auto Top-up
                                            </label>
                                            <small class="form-text text-muted">Automatically top-up when balance is low</small>
                                        </div>
                                    </div>

                                    <div class="form-group auto-topup-settings" style="{{ old('auto_topup_enabled', $smsWallet->auto_topup_enabled) ? '' : 'display: none;' }}">
                                        <label for="auto_topup_amount">Auto Top-up Amount</label>
                                        <input type="number" 
                                               class="form-control @error('auto_topup_amount') is-invalid @enderror" 
                                               id="auto_topup_amount" 
                                               name="auto_topup_amount" 
                                               value="{{ old('auto_topup_amount', $smsWallet->auto_topup_amount ?? 100) }}" 
                                               min="1" 
                                               step="1">
                                        @error('auto_topup_amount')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">Amount to add when auto top-up triggers</small>
                                    </div>

                                    <div class="form-group">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" 
                                                   class="custom-control-input" 
                                                   id="send_notifications" 
                                                   name="send_notifications" 
                                                   value="1" 
                                                   {{ old('send_notifications', $smsWallet->send_notifications ?? true) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="send_notifications">
                                                Send Balance Notifications
                                            </label>
                                            <small class="form-text text-muted">Notify tenant about low balance and transactions</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Balance Adjustment Section -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <h5>Adjust Balance</h5>
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="adjustment_type">Adjustment Type</label>
                                                        <select class="form-control" id="adjustment_type" name="adjustment_type">
                                                            <option value="">No Adjustment</option>
                                                            <option value="add">Add Credits</option>
                                                            <option value="deduct">Deduct Credits</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="adjustment_amount">Amount</label>
                                                        <input type="number" 
                                                               class="form-control" 
                                                               id="adjustment_amount" 
                                                               name="adjustment_amount" 
                                                               min="1" 
                                                               step="1" 
                                                               disabled>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="adjustment_reason">Reason</label>
                                                        <input type="text" 
                                                               class="form-control" 
                                                               id="adjustment_reason" 
                                                               name="adjustment_reason" 
                                                               placeholder="Reason for adjustment" 
                                                               disabled>
                                                    </div>
                                                </div>
                                            </div>
                                            <small class="form-text text-muted">
                                                <i class="fas fa-info-circle"></i> 
                                                Balance adjustments will be logged and the tenant will be notified if notifications are enabled.
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update SMS Wallet
                            </button>
                            <a href="{{ route('admin.sms-wallets.show', $smsWallet->id) }}" class="btn btn-info">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                            <a href="{{ route('admin.sms-wallets.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                @else
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <h5>SMS Wallet Not Found</h5>
                            <p>The requested SMS wallet could not be found.</p>
                            <a href="{{ route('admin.sms-wallets.index') }}" class="btn btn-primary">Back to SMS Wallets</a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Toggle auto top-up settings
    $('#auto_topup_enabled').change(function() {
        if ($(this).is(':checked')) {
            $('.auto-topup-settings').slideDown();
        } else {
            $('.auto-topup-settings').slideUp();
        }
    });

    // Handle balance adjustment type change
    $('#adjustment_type').change(function() {
        const adjustmentType = $(this).val();
        const amountField = $('#adjustment_amount');
        const reasonField = $('#adjustment_reason');
        
        if (adjustmentType) {
            amountField.prop('disabled', false).prop('required', true);
            reasonField.prop('disabled', false).prop('required', true);
            
            if (adjustmentType === 'deduct') {
                amountField.attr('max', {{ $smsWallet->balance ?? 0 }});
                amountField.attr('placeholder', 'Max: {{ $smsWallet->balance ?? 0 }} credits');
            } else {
                amountField.removeAttr('max');
                amountField.attr('placeholder', 'Enter amount to add');
            }
        } else {
            amountField.prop('disabled', true).prop('required', false).val('');
            reasonField.prop('disabled', true).prop('required', false).val('');
        }
    });

    // Form validation
    $('#editWalletForm').submit(function(e) {
        let isValid = true;
        let errorMessage = '';

        // Check if tenant is selected
        if (!$('#tenant_id').val()) {
            isValid = false;
            errorMessage += 'Please select a tenant.\n';
        }

        // Check limits
        const dailyLimit = $('#daily_limit').val();
        const monthlyLimit = $('#monthly_limit').val();
        
        if (dailyLimit && monthlyLimit) {
            if (parseInt(dailyLimit) > parseInt(monthlyLimit)) {
                isValid = false;
                errorMessage += 'Daily limit cannot be greater than monthly limit.\n';
            }
        }

        // Check auto top-up settings
        if ($('#auto_topup_enabled').is(':checked')) {
            const autoTopupAmount = parseInt($('#auto_topup_amount').val());
            if (isNaN(autoTopupAmount) || autoTopupAmount <= 0) {
                isValid = false;
                errorMessage += 'Auto top-up amount must be a valid positive number.\n';
            }
        }

        // Check balance adjustment
        const adjustmentType = $('#adjustment_type').val();
        if (adjustmentType) {
            const adjustmentAmount = parseInt($('#adjustment_amount').val());
            const adjustmentReason = $('#adjustment_reason').val().trim();
            
            if (isNaN(adjustmentAmount) || adjustmentAmount <= 0) {
                isValid = false;
                errorMessage += 'Adjustment amount must be a valid positive number.\n';
            }
            
            if (!adjustmentReason) {
                isValid = false;
                errorMessage += 'Please provide a reason for the balance adjustment.\n';
            }
            
            if (adjustmentType === 'deduct' && adjustmentAmount > {{ $smsWallet->balance ?? 0 }}) {
                isValid = false;
                errorMessage += 'Cannot deduct more than the current balance ({{ $smsWallet->balance ?? 0 }} credits).\n';
            }
        }

        if (!isValid) {
            e.preventDefault();
            alert('Please fix the following errors:\n\n' + errorMessage);
            return false;
        }

        // Show loading state
        $(this).find('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');
    });

    // Real-time validation feedback
    $('#daily_limit, #monthly_limit, #auto_topup_amount, #adjustment_amount').on('input', function() {
        const value = parseInt($(this).val());
        
        if ($(this).val() && (isNaN(value) || value < 0)) {
            $(this).addClass('is-invalid');
            if (!$(this).next('.invalid-feedback').length) {
                $(this).after('<div class="invalid-feedback">Please enter a valid positive number</div>');
            }
        } else {
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
        }
    });
});
</script>
@endsection