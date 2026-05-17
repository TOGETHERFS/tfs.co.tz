@extends('layouts.tenant')

@section('title', 'Edit SMS Notification')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit SMS Notification</h3>
                    <div class="card-tools">
                        <a href="{{ route('tenant.sms-notifications.show', $notification->id) }}" class="btn btn-info btn-sm">
                            <i class="fas fa-eye"></i> View
                        </a>
                        <a href="{{ route('tenant.sms-notifications.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to SMS Notifications
                        </a>
                    </div>
                </div>
                <form action="{{ route('tenant.sms-notifications.update', $notification->id) }}" method="POST" id="editNotificationForm">
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

                        @if($notification->status === 'sent')
                            <div class="alert alert-warning">
                                <i class="fas fa-info-circle"></i> This notification has already been sent. Only limited fields can be modified.
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="title">Notification Title <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control @error('title') is-invalid @enderror" 
                                           id="title" 
                                           name="title" 
                                           value="{{ old('title', $notification->title) }}" 
                                           placeholder="Enter notification title" 
                                           {{ $notification->status === 'sent' ? 'readonly' : '' }}
                                           required>
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="type">Notification Type <span class="text-danger">*</span></label>
                                    <select class="form-control @error('type') is-invalid @enderror" 
                                            id="type" name="type" 
                                            {{ $notification->status === 'sent' ? 'disabled' : '' }}
                                            required>
                                        <option value="">Select Type</option>
                                        <option value="loan_reminder" {{ old('type', $notification->type) == 'loan_reminder' ? 'selected' : '' }}>Loan Reminder</option>
                                        <option value="payment_due" {{ old('type', $notification->type) == 'payment_due' ? 'selected' : '' }}>Payment Due</option>
                                        <option value="payment_received" {{ old('type', $notification->type) == 'payment_received' ? 'selected' : '' }}>Payment Received</option>
                                        <option value="account_update" {{ old('type', $notification->type) == 'account_update' ? 'selected' : '' }}>Account Update</option>
                                        <option value="promotional" {{ old('type', $notification->type) == 'promotional' ? 'selected' : '' }}>Promotional</option>
                                        <option value="general" {{ old('type', $notification->type) == 'general' ? 'selected' : '' }}>General</option>
                                    </select>
                                    @error('type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="priority">Priority</label>
                                    <select class="form-control @error('priority') is-invalid @enderror" 
                                            id="priority" name="priority"
                                            {{ $notification->status === 'sent' ? 'disabled' : '' }}>
                                        <option value="normal" {{ old('priority', $notification->priority) == 'normal' ? 'selected' : '' }}>Normal</option>
                                        <option value="high" {{ old('priority', $notification->priority) == 'high' ? 'selected' : '' }}>High</option>
                                        <option value="low" {{ old('priority', $notification->priority) == 'low' ? 'selected' : '' }}>Low</option>
                                    </select>
                                    @error('priority')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select class="form-control @error('status') is-invalid @enderror" 
                                            id="status" name="status">
                                        <option value="draft" {{ old('status', $notification->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                                        <option value="scheduled" {{ old('status', $notification->status) == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                                        <option value="sending" {{ old('status', $notification->status) == 'sending' ? 'selected' : '' }} disabled>Sending</option>
                                        <option value="sent" {{ old('status', $notification->status) == 'sent' ? 'selected' : '' }} disabled>Sent</option>
                                        <option value="failed" {{ old('status', $notification->status) == 'failed' ? 'selected' : '' }}>Failed</option>
                                        <option value="cancelled" {{ old('status', $notification->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sender_id">Sender ID</label>
                                    <select class="form-control @error('sender_id') is-invalid @enderror" 
                                            id="sender_id" name="sender_id"
                                            {{ $notification->status === 'sent' ? 'disabled' : '' }}>
                                        <option value="">Use Default Sender ID</option>
                                        @if(isset($senderIds))
                                            @foreach($senderIds as $senderId)
                                                <option value="{{ $senderId->sender_id }}" 
                                                        {{ old('sender_id', $notification->sender_id) == $senderId->sender_id ? 'selected' : '' }}>
                                                    {{ $senderId->sender_id }} 
                                                    @if($senderId->status === 'approved')
                                                        <span class="text-success">(Approved)</span>
                                                    @else
                                                        <span class="text-warning">({{ ucfirst($senderId->status) }})</span>
                                                    @endif
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @error('sender_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="scheduled_at">Scheduled Date & Time</label>
                                    <input type="datetime-local" 
                                           class="form-control @error('scheduled_at') is-invalid @enderror" 
                                           id="scheduled_at" 
                                           name="scheduled_at" 
                                           value="{{ old('scheduled_at', $notification->scheduled_at ? $notification->scheduled_at->format('Y-m-d\TH:i') : '') }}"
                                           {{ $notification->status === 'sent' ? 'readonly' : '' }}>
                                    @error('scheduled_at')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Leave empty for immediate sending</small>
                                </div>

                                @if($notification->sent_at)
                                <div class="form-group">
                                    <label>Sent At</label>
                                    <input type="text" class="form-control" 
                                           value="{{ $notification->sent_at->format('Y-m-d H:i:s') }}" readonly>
                                </div>
                                @endif

                                @if($notification->delivery_stats)
                                <div class="form-group">
                                    <label>Delivery Statistics</label>
                                    <div class="row">
                                        <div class="col-4">
                                            <small class="text-muted">Sent</small>
                                            <div class="text-success font-weight-bold">{{ $notification->delivery_stats['sent'] ?? 0 }}</div>
                                        </div>
                                        <div class="col-4">
                                            <small class="text-muted">Failed</small>
                                            <div class="text-danger font-weight-bold">{{ $notification->delivery_stats['failed'] ?? 0 }}</div>
                                        </div>
                                        <div class="col-4">
                                            <small class="text-muted">Rate</small>
                                            <div class="text-info font-weight-bold">
                                                {{ number_format(($notification->delivery_stats['sent'] ?? 0) / max(($notification->delivery_stats['total'] ?? 1), 1) * 100, 1) }}%
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="message">Message Content <span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('message') is-invalid @enderror" 
                                              id="message" 
                                              name="message" 
                                              rows="4" 
                                              placeholder="Enter your SMS message here..." 
                                              {{ $notification->status === 'sent' ? 'readonly' : '' }}
                                              required>{{ old('message', $notification->message) }}</textarea>
                                    @error('message')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <small class="text-muted">
                                                    Characters: <span id="char-count">{{ strlen($notification->message) }}</span>/160
                                                    | SMS Parts: <span id="sms-parts">{{ ceil(strlen($notification->message) / 160) ?: 1 }}</span>
                                                </small>
                                            </div>
                                            <div class="col-md-6 text-right">
                                                <small class="text-muted">
                                                    Credits Used: <span id="credits-used">{{ $notification->credits_used ?? 0 }}</span>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <h5>Target Audience</h5>
                                <div class="card bg-light">
                                    <div class="card-body">
                                        @if($notification->status === 'sent')
                                            <div class="alert alert-info">
                                                <strong>Audience Type:</strong> {{ ucfirst(str_replace('_', ' ', $notification->audience_type)) }}<br>
                                                <strong>Total Recipients:</strong> {{ $notification->total_recipients ?? 0 }}<br>
                                                @if($notification->audience_filters)
                                                    <strong>Filters Applied:</strong>
                                                    <ul class="mb-0 mt-1">
                                                        @foreach($notification->audience_filters as $key => $value)
                                                            <li>{{ ucfirst(str_replace('_', ' ', $key)) }}: {{ $value }}</li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </div>
                                        @else
                                            <div class="form-group">
                                                <label for="audience_type">Audience Type <span class="text-danger">*</span></label>
                                                <select class="form-control @error('audience_type') is-invalid @enderror" 
                                                        id="audience_type" name="audience_type" required>
                                                    <option value="">Select Audience</option>
                                                    <option value="all_clients" {{ old('audience_type', $notification->audience_type) == 'all_clients' ? 'selected' : '' }}>All Clients</option>
                                                    <option value="active_loans" {{ old('audience_type', $notification->audience_type) == 'active_loans' ? 'selected' : '' }}>Clients with Active Loans</option>
                                                    <option value="overdue_loans" {{ old('audience_type', $notification->audience_type) == 'overdue_loans' ? 'selected' : '' }}>Clients with Overdue Loans</option>
                                                    <option value="specific_group" {{ old('audience_type', $notification->audience_type) == 'specific_group' ? 'selected' : '' }}>Specific Group</option>
                                                    <option value="specific_clients" {{ old('audience_type', $notification->audience_type) == 'specific_clients' ? 'selected' : '' }}>Specific Clients</option>
                                                    <option value="custom_filter" {{ old('audience_type', $notification->audience_type) == 'custom_filter' ? 'selected' : '' }}>Custom Filter</option>
                                                </select>
                                                @error('audience_type')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="audience-options" id="specific_group_options" style="display: none;">
                                                <div class="form-group">
                                                    <label for="group_id">Select Group</label>
                                                    <select class="form-control" id="group_id" name="group_id">
                                                        <option value="">Choose a group</option>
                                                        @if(isset($groups))
                                                            @foreach($groups as $group)
                                                                <option value="{{ $group->id }}" 
                                                                        {{ old('group_id', $notification->group_id) == $group->id ? 'selected' : '' }}>
                                                                    {{ $group->name }} ({{ $group->members_count ?? 0 }} members)
                                                                </option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="audience-options" id="specific_clients_options" style="display: none;">
                                                <div class="form-group">
                                                    <label for="client_ids">Select Clients</label>
                                                    <select class="form-control" id="client_ids" name="client_ids[]" multiple>
                                                        @if(isset($clients))
                                                            @foreach($clients as $client)
                                                                <option value="{{ $client->id }}" 
                                                                        {{ in_array($client->id, old('client_ids', $notification->client_ids ?? [])) ? 'selected' : '' }}>
                                                                    {{ $client->first_name }} {{ $client->last_name }} ({{ $client->phone }})
                                                                </option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                    <small class="form-text text-muted">Hold Ctrl/Cmd to select multiple clients</small>
                                                </div>
                                            </div>

                                            <div class="audience-options" id="custom_filter_options" style="display: none;">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label for="loan_status">Loan Status</label>
                                                            <select class="form-control" id="loan_status" name="loan_status">
                                                                <option value="">Any Status</option>
                                                                <option value="active" {{ old('loan_status', $notification->audience_filters['loan_status'] ?? '') == 'active' ? 'selected' : '' }}>Active</option>
                                                                <option value="completed" {{ old('loan_status', $notification->audience_filters['loan_status'] ?? '') == 'completed' ? 'selected' : '' }}>Completed</option>
                                                                <option value="overdue" {{ old('loan_status', $notification->audience_filters['loan_status'] ?? '') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                                                                <option value="defaulted" {{ old('loan_status', $notification->audience_filters['loan_status'] ?? '') == 'defaulted' ? 'selected' : '' }}>Defaulted</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label for="days_overdue">Days Overdue</label>
                                                            <select class="form-control" id="days_overdue" name="days_overdue">
                                                                <option value="">Any</option>
                                                                <option value="1-7" {{ old('days_overdue', $notification->audience_filters['days_overdue'] ?? '') == '1-7' ? 'selected' : '' }}>1-7 days</option>
                                                                <option value="8-30" {{ old('days_overdue', $notification->audience_filters['days_overdue'] ?? '') == '8-30' ? 'selected' : '' }}>8-30 days</option>
                                                                <option value="31-90" {{ old('days_overdue', $notification->audience_filters['days_overdue'] ?? '') == '31-90' ? 'selected' : '' }}>31-90 days</option>
                                                                <option value="90+" {{ old('days_overdue', $notification->audience_filters['days_overdue'] ?? '') == '90+' ? 'selected' : '' }}>90+ days</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label for="client_status">Client Status</label>
                                                            <select class="form-control" id="client_status" name="client_status">
                                                                <option value="">Any Status</option>
                                                                <option value="active" {{ old('client_status', $notification->audience_filters['client_status'] ?? '') == 'active' ? 'selected' : '' }}>Active</option>
                                                                <option value="inactive" {{ old('client_status', $notification->audience_filters['client_status'] ?? '') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                                                <option value="suspended" {{ old('client_status', $notification->audience_filters['client_status'] ?? '') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <button type="button" class="btn btn-info btn-sm" id="preview-audience">
                                                    <i class="fas fa-eye"></i> Preview Recipients
                                                </button>
                                                <span class="ml-2">
                                                    <small class="text-muted">Estimated recipients: <span id="recipient-count">{{ $notification->total_recipients ?? 0 }}</span></small>
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($notification->error_details)
                        <div class="row">
                            <div class="col-12">
                                <h5>Error Details</h5>
                                <div class="card bg-danger">
                                    <div class="card-body text-white">
                                        <pre>{{ $notification->error_details }}</pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($notification->status !== 'sent')
                        <div class="row">
                            <div class="col-12">
                                <h5>Message Variables</h5>
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <p class="mb-2">You can use the following variables in your message:</p>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <ul class="list-unstyled">
                                                    <li><code>{first_name}</code> - Client's first name</li>
                                                    <li><code>{last_name}</code> - Client's last name</li>
                                                    <li><code>{full_name}</code> - Client's full name</li>
                                                    <li><code>{phone}</code> - Client's phone number</li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <ul class="list-unstyled">
                                                    <li><code>{loan_amount}</code> - Loan amount</li>
                                                    <li><code>{balance}</code> - Outstanding balance</li>
                                                    <li><code>{due_date}</code> - Next payment due date</li>
                                                    <li><code>{company_name}</code> - Your company name</li>
                                                </ul>
                                            </div>
                                        </div>
                                        <small class="text-muted">Variables will be automatically replaced with actual values when sending.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    <div class="card-footer">
                        @if($notification->status !== 'sent')
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Notification
                            </button>
                            
                            @if($notification->status === 'draft')
                                <button type="button" class="btn btn-success" id="send-now-btn">
                                    <i class="fas fa-paper-plane"></i> Send Now
                                </button>
                            @endif
                            
                            @if(in_array($notification->status, ['scheduled', 'failed']))
                                <button type="button" class="btn btn-warning" id="reschedule-btn">
                                    <i class="fas fa-clock"></i> Reschedule
                                </button>
                            @endif
                        @endif
                        
                        @if($notification->status === 'scheduled')
                            <button type="button" class="btn btn-danger" id="cancel-btn">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        @endif
                        
                        <a href="{{ route('tenant.sms-notifications.show', $notification->id) }}" class="btn btn-info">
                            <i class="fas fa-eye"></i> View
                        </a>
                        <a href="{{ route('tenant.sms-notifications.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
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
    const notificationStatus = '{{ $notification->status }}';
    
    // Character count and SMS parts calculation (only if not sent)
    if (notificationStatus !== 'sent') {
        $('#message').on('input', function() {
            const message = $(this).val();
            const charCount = message.length;
            const smsParts = Math.ceil(charCount / 160) || 1;
            
            $('#char-count').text(charCount);
            $('#sms-parts').text(smsParts);
            
            // Color coding for character count
            if (charCount > 160) {
                $('#char-count').addClass('text-warning');
            } else {
                $('#char-count').removeClass('text-warning');
            }
        });

        // Show/hide audience options
        $('#audience_type').change(function() {
            const audienceType = $(this).val();
            
            // Hide all audience options
            $('.audience-options').hide();
            
            // Show relevant options
            if (audienceType === 'specific_group') {
                $('#specific_group_options').slideDown();
            } else if (audienceType === 'specific_clients') {
                $('#specific_clients_options').slideDown();
            } else if (audienceType === 'custom_filter') {
                $('#custom_filter_options').slideDown();
            }
            
            // Update recipient count
            updateRecipientCount();
        });

        // Update recipient count when filters change
        $('#group_id, #client_ids, #loan_status, #days_overdue, #client_status').change(function() {
            updateRecipientCount();
        });

        // Preview audience
        $('#preview-audience').click(function() {
            // This would typically make an AJAX call to get the actual recipient list
            alert('Preview functionality would show the list of recipients based on current filters.');
        });

        // Initialize audience options display
        $('#audience_type').trigger('change');
    }

    // Set minimum datetime for scheduling
    const now = new Date();
    const minDateTime = new Date(now.getTime() + 60 * 60 * 1000); // 1 hour from now
    $('#scheduled_at').attr('min', minDateTime.toISOString().slice(0, 16));

    // Send now button
    $('#send-now-btn').click(function() {
        if (confirm('Are you sure you want to send this notification immediately?')) {
            // Add a hidden field to indicate immediate sending
            $('<input>').attr({
                type: 'hidden',
                name: 'send_immediately',
                value: '1'
            }).appendTo('#editNotificationForm');
            
            $('#editNotificationForm').submit();
        }
    });

    // Reschedule button
    $('#reschedule-btn').click(function() {
        const newDateTime = prompt('Enter new schedule date and time (YYYY-MM-DD HH:MM):');
        if (newDateTime) {
            $('#scheduled_at').val(newDateTime.replace(' ', 'T'));
            $('#status').val('scheduled');
        }
    });

    // Cancel button
    $('#cancel-btn').click(function() {
        if (confirm('Are you sure you want to cancel this scheduled notification?')) {
            $('#status').val('cancelled');
            $('#editNotificationForm').submit();
        }
    });

    // Form validation
    $('#editNotificationForm').submit(function(e) {
        if (notificationStatus === 'sent') {
            return true; // Allow form submission for sent notifications (limited updates)
        }

        let isValid = true;
        let errorMessage = '';

        // Check message content
        const message = $('#message').val().trim();
        if (!message) {
            isValid = false;
            errorMessage += 'Message content is required.\n';
        }

        // Check audience selection
        const audienceType = $('#audience_type').val();
        if (!audienceType) {
            isValid = false;
            errorMessage += 'Please select an audience type.\n';
        }

        // Check specific audience selections
        if (audienceType === 'specific_group' && !$('#group_id').val()) {
            isValid = false;
            errorMessage += 'Please select a group.\n';
        }

        if (audienceType === 'specific_clients' && $('#client_ids').val().length === 0) {
            isValid = false;
            errorMessage += 'Please select at least one client.\n';
        }

        // Check schedule datetime if status is scheduled
        const status = $('#status').val();
        const scheduledAt = $('#scheduled_at').val();
        if (status === 'scheduled' && scheduledAt) {
            const scheduledDate = new Date(scheduledAt);
            const now = new Date();
            
            if (scheduledDate <= now) {
                isValid = false;
                errorMessage += 'Scheduled time must be in the future.\n';
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

    function updateRecipientCount() {
        // This would typically make an AJAX call to get the actual count
        // For now, we'll use placeholder logic
        const audienceType = $('#audience_type').val();
        let count = 0;
        
        switch (audienceType) {
            case 'all_clients':
                count = {{ $totalClients ?? 0 }};
                break;
            case 'active_loans':
                count = {{ $activeLoansCount ?? 0 }};
                break;
            case 'overdue_loans':
                count = {{ $overdueLoansCount ?? 0 }};
                break;
            case 'specific_group':
                const groupId = $('#group_id').val();
                if (groupId) {
                    // Get count from selected group
                    const selectedOption = $('#group_id option:selected');
                    const text = selectedOption.text();
                    const match = text.match(/\((\d+) members\)/);
                    count = match ? parseInt(match[1]) : 0;
                }
                break;
            case 'specific_clients':
                count = $('#client_ids').val().length;
                break;
            default:
                count = {{ $notification->total_recipients ?? 0 }};
        }
        
        $('#recipient-count').text(count);
    }
});
</script>
@endsection