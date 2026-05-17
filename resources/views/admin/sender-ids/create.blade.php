@extends('layouts.admin')

@section('title', 'Create Sender ID')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Create New Sender ID</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.sender-ids.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Sender IDs
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.sender-ids.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sender_id">Sender ID</label>
                                    <input type="text" class="form-control @error('sender_id') is-invalid @enderror" 
                                           id="sender_id" name="sender_id" value="{{ old('sender_id') }}" 
                                           placeholder="e.g., COMPANY, BRAND123" required>
                                    <small class="form-text text-muted">
                                        Sender ID should be alphanumeric, max 11 characters, no spaces or special characters.
                                    </small>
                                    @error('sender_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tenant_id">Tenant</label>
                                    <select class="form-control @error('tenant_id') is-invalid @enderror" 
                                            id="tenant_id" name="tenant_id" required>
                                        <option value="">Select Tenant</option>
                                        @if(isset($tenants))
                                            @foreach($tenants as $tenant)
                                                <option value="{{ $tenant->id }}" {{ old('tenant_id') == $tenant->id ? 'selected' : '' }}>
                                                    {{ $tenant->name }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @error('tenant_id')
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
                                        <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="approved" {{ old('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                        <option value="rejected" {{ old('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="type">Type</label>
                                    <select class="form-control @error('type') is-invalid @enderror" 
                                            id="type" name="type">
                                        <option value="generic" {{ old('type') == 'generic' ? 'selected' : '' }}>Generic</option>
                                        <option value="promotional" {{ old('type') == 'promotional' ? 'selected' : '' }}>Promotional</option>
                                        <option value="transactional" {{ old('type') == 'transactional' ? 'selected' : '' }}>Transactional</option>
                                        <option value="otp" {{ old('type') == 'otp' ? 'selected' : '' }}>OTP</option>
                                    </select>
                                    @error('type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3" 
                                      placeholder="Describe the purpose and usage of this Sender ID">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="use_case">Use Case</label>
                            <textarea class="form-control @error('use_case') is-invalid @enderror" 
                                      id="use_case" name="use_case" rows="2" 
                                      placeholder="Explain how this Sender ID will be used">{{ old('use_case') }}</textarea>
                            @error('use_case')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" 
                                               {{ old('is_active', true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">
                                            Active
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="auto_approve" name="auto_approve" value="1">
                                        <label class="form-check-label" for="auto_approve">
                                            Auto-approve future requests from this tenant
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle"></i> Sender ID Guidelines</h6>
                            <ul class="mb-0">
                                <li>Sender ID must be alphanumeric only (A-Z, 0-9)</li>
                                <li>Maximum 11 characters</li>
                                <li>No spaces or special characters allowed</li>
                                <li>Should represent your brand or company name</li>
                                <li>Must comply with local telecommunications regulations</li>
                            </ul>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Create Sender ID
                            </button>
                            <a href="{{ route('admin.sender-ids.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Auto-uppercase sender ID input
    $('#sender_id').on('input', function() {
        this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
    });
    
    // Limit sender ID length
    $('#sender_id').attr('maxlength', 11);
});
</script>
@endsection