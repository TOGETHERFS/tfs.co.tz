@extends('layouts.admin')

@section('title', 'Create SMS Wallet')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Create New SMS Wallet</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.sms-wallets.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to SMS Wallets
                        </a>
                    </div>
                </div>
                <form action="{{ route('admin.sms-wallets.store') }}" method="POST" id="createWalletForm">
                    @csrf
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
                                                        {{ old('tenant_id') == $tenant->id ? 'selected' : '' }}>
                                                    {{ $tenant->name }} ({{ $tenant->email }})
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @error('tenant_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Select the tenant for this SMS wallet</small>
                                </div>

                                <div class="form-group">
                                    <label for="initial_balance">Initial Balance <span class="text-danger">*</span></label>
                                    <input type="number" 
                                           class="form-control @error('initial_balance') is-invalid @enderror" 
                                           id="initial_balance" 
                                           name="initial_balance" 
                                           value="{{ old('initial_balance', 0) }}" 
                                           min="0" 
                                           step="1" 
                                           required>
                                    @error('initial_balance')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Number of SMS credits to start with</small>
                                </div>

                                <div class="form-group">
                                    <label for="currency">Currency</label>
                                    <select class="form-control @error('currency') is-invalid @enderror" 
                                            id="currency" name="currency">
                                        <option value="TZS" {{ old('currency', 'TZS') == 'TZS' ? 'selected' : '' }}>TZS - Tanzanian Shilling</option>
                                        <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
                                        <option value="EUR" {{ old('currency') == 'EUR' ? 'selected' : '' }}>EUR - Euro</option>
                                        <option value="KES" {{ old('currency') == 'KES' ? 'selected' : '' }}>KES - Kenyan Shilling</option>
                                        <option value="UGX" {{ old('currency') == 'UGX' ? 'selected' : '' }}>UGX - Ugandan Shilling</option>
                                    </select>
                                    @error('currency')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Currency for billing and transactions</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="low_balance_threshold">Low Balance Threshold</label>
                                    <input type="number" 
                                           class="form-control @error('low_balance_threshold') is-invalid @enderror" 
                                           id="low_balance_threshold" 
                                           name="low_balance_threshold" 
                                           value="{{ old('low_balance_threshold', 10) }}" 
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
                                           value="{{ old('daily_limit') }}" 
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
                                           value="{{ old('monthly_limit') }}" 
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
                                              placeholder="Optional description for this SMS wallet">{{ old('description') }}</textarea>
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
                                               {{ old('is_active', true) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="is_active">
                                            Active Wallet
                                        </label>
                                        <small class="form-text text-muted">Uncheck to create an inactive wallet</small>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" 
                                               class="custom-control-input" 
                                               id="auto_topup_enabled" 
                                               name="auto_topup_enabled" 
                                               value="1" 
                                               {{ old('auto_topup_enabled') ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="auto_topup_enabled">
                                            Enable Auto Top-up
                                        </label>
                                        <small class="form-text text-muted">Automatically top-up when balance is low</small>
                                    </div>
                                </div>

                                <div class="form-group auto-topup-settings" style="display: none;">
                                    <label for="auto_topup_amount">Auto Top-up Amount</label>
                                    <input type="number" 
                                           class="form-control @error('auto_topup_amount') is-invalid @enderror" 
                                           id="auto_topup_amount" 
                                           name="auto_topup_amount" 
                                           value="{{ old('auto_topup_amount', 100) }}" 
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
                                               {{ old('send_notifications', true) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="send_notifications">
                                            Send Balance Notifications
                                        </label>
                                        <small class="form-text text-muted">Notify tenant about low balance and transactions</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create SMS Wallet
                        </button>
                        <a href="{{ route('admin.sms-wallets.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
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

    // Initialize auto top-up settings visibility
    if ($('#auto_topup_enabled').is(':checked')) {
        $('.auto-topup-settings').show();
    }

    // Form validation
    $('#createWalletForm').submit(function(e) {
        let isValid = true;
        let errorMessage = '';

        // Check if tenant is selected
        if (!$('#tenant_id').val()) {
            isValid = false;
            errorMessage += 'Please select a tenant.\n';
        }

        // Check initial balance
        const initialBalance = parseInt($('#initial_balance').val());
        if (isNaN(initialBalance) || initialBalance < 0) {
            isValid = false;
            errorMessage += 'Initial balance must be a valid number (0 or greater).\n';
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

        if (!isValid) {
            e.preventDefault();
            alert('Please fix the following errors:\n\n' + errorMessage);
            return false;
        }

        // Show loading state
        $(this).find('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creating...');
    });

    // Real-time validation feedback
    $('#initial_balance, #daily_limit, #monthly_limit, #auto_topup_amount').on('input', function() {
        const value = parseInt($(this).val());
        const fieldName = $(this).attr('name').replace('_', ' ');
        
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