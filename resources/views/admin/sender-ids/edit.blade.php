@extends('layouts.admin')

@section('title', 'Edit Sender ID')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit Sender ID</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.sender-ids.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Sender IDs
                        </a>
                        @if(isset($senderId))
                            <a href="{{ route('admin.sender-ids.show', $senderId->id) }}" class="btn btn-info btn-sm">
                                <i class="fas fa-eye"></i> View
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @if(isset($senderId))
                        <div class="row mb-3">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <h6><i class="fas fa-info-circle"></i> Current Sender ID: <code>{{ $senderId->sender_id }}</code></h6>
                                    <p class="mb-0">Created: {{ $senderId->created_at->format('d M Y H:i') }} | 
                                    Status: 
                                    @if($senderId->status == 'approved')
                                        <span class="badge badge-success">Approved</span>
                                    @elseif($senderId->status == 'pending')
                                        <span class="badge badge-warning">Pending</span>
                                    @elseif($senderId->status == 'rejected')
                                        <span class="badge badge-danger">Rejected</span>
                                    @else
                                        <span class="badge badge-secondary">{{ ucfirst($senderId->status ?? 'Unknown') }}</span>
                                    @endif
                                    </p>
                                </div>
                            </div>
                        </div>

                        <form action="{{ route('admin.sender-ids.update', $senderId->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="sender_id">Sender ID</label>
                                        <input type="text" class="form-control @error('sender_id') is-invalid @enderror" 
                                               id="sender_id" name="sender_id" value="{{ old('sender_id', $senderId->sender_id) }}" 
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
                                                    <option value="{{ $tenant->id }}" 
                                                            {{ old('tenant_id', $senderId->tenant_id) == $tenant->id ? 'selected' : '' }}>
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
                                            <option value="pending" {{ old('status', $senderId->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="approved" {{ old('status', $senderId->status) == 'approved' ? 'selected' : '' }}>Approved</option>
                                            <option value="rejected" {{ old('status', $senderId->status) == 'rejected' ? 'selected' : '' }}>Rejected</option>
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
                                            <option value="generic" {{ old('type', $senderId->type) == 'generic' ? 'selected' : '' }}>Generic</option>
                                            <option value="promotional" {{ old('type', $senderId->type) == 'promotional' ? 'selected' : '' }}>Promotional</option>
                                            <option value="transactional" {{ old('type', $senderId->type) == 'transactional' ? 'selected' : '' }}>Transactional</option>
                                            <option value="otp" {{ old('type', $senderId->type) == 'otp' ? 'selected' : '' }}>OTP</option>
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
                                          placeholder="Describe the purpose and usage of this Sender ID">{{ old('description', $senderId->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="use_case">Use Case</label>
                                <textarea class="form-control @error('use_case') is-invalid @enderror" 
                                          id="use_case" name="use_case" rows="2" 
                                          placeholder="Explain how this Sender ID will be used">{{ old('use_case', $senderId->use_case) }}</textarea>
                                @error('use_case')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group" id="rejection_reason_group" style="display: none;">
                                <label for="rejection_reason">Rejection Reason</label>
                                <textarea class="form-control @error('rejection_reason') is-invalid @enderror" 
                                          id="rejection_reason" name="rejection_reason" rows="2" 
                                          placeholder="Provide reason for rejection">{{ old('rejection_reason', $senderId->rejection_reason) }}</textarea>
                                @error('rejection_reason')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" 
                                                   {{ old('is_active', $senderId->is_active) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="is_active">
                                                Active
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
                                    <i class="fas fa-save"></i> Update Sender ID
                                </button>
                                <a href="{{ route('admin.sender-ids.show', $senderId->id) }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    @else
                        <div class="alert alert-warning">
                            <h5>Sender ID Not Found</h5>
                            <p>The requested Sender ID could not be found.</p>
                            <a href="{{ route('admin.sender-ids.index') }}" class="btn btn-primary">Back to Sender IDs</a>
                        </div>
                    @endif
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
    
    // Show/hide rejection reason based on status
    function toggleRejectionReason() {
        if ($('#status').val() === 'rejected') {
            $('#rejection_reason_group').show();
            $('#rejection_reason').prop('required', true);
        } else {
            $('#rejection_reason_group').hide();
            $('#rejection_reason').prop('required', false);
        }
    }
    
    // Initial check
    toggleRejectionReason();
    
    // On status change
    $('#status').change(toggleRejectionReason);
});
</script>
@endsection