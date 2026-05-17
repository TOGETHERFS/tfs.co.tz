@extends('layouts.tenant')

@section('title', 'Create SMS Notification')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Create New SMS Notification</h3>
                    <div class="card-tools">
                        <a href="{{ route('tenant.sms-notifications.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to SMS Notifications
                        </a>
                    </div>
                </div>
                <form action="{{ route('tenant.sms-notifications.store') }}" method="POST" id="createNotificationForm">
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
                                    <label for="title">Notification Title <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control @error('title') is-invalid @enderror" 
                                           id="title" 
                                           name="title" 
                                           value="{{ old('title') }}" 
                                           placeholder="Enter notification title" 
                                           required>
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">A descriptive title for this notification</small>
                                </div>

                                <div class="form-group">
                                    <label for="type">Notification Type <span class="text-danger">*</span></label>
                                    <select class="form-control @error('type') is-invalid @enderror" 
                                            id="type" name="type" required>
                                        <option value="">Select Type</option>
                                        <option value="loan_reminder" {{ old('type') == 'loan_reminder' ? 'selected' : '' }}>Loan Reminder</option>
                                        <option value="payment_due" {{ old('type') == 'payment_due' ? 'selected' : '' }}>Payment Due</option>
                                        <option value="payment_received" {{ old('type') == 'payment_received' ? 'selected' : '' }}>Payment Received</option>
                                        <option value="account_update" {{ old('type') == 'account_update' ? 'selected' : '' }}>Account Update</option>
                                        <option value="promotional" {{ old('type') == 'promotional' ? 'selected' : '' }}>Promotional</option>
                                        <option value="general" {{ old('type') == 'general' ? 'selected' : '' }}>General</option>
                                    </select>
                                    @error('type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="priority">Priority</label>
                                    <select class="form-control @error('priority') is-invalid @enderror" 
                                            id="priority" name="priority">
                                        <option value="normal" {{ old('priority', 'normal') == 'normal' ? 'selected' : '' }}>Normal</option>
                                        <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>High</option>
                                        <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Low</option>
                                    </select>
                                    @error('priority')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sender_id">Sender ID</label>
                                    <select class="form-control @error('sender_id') is-invalid @enderror" 
                                            id="sender_id" name="sender_id">
                                        <option value="">Use Default Sender ID</option>
                                        @if(isset($senderIds))
                                            @foreach($senderIds as $senderId)
                                                <option value="{{ $senderId->sender_id }}" 
                                                        {{ old('sender_id') == $senderId->sender_id ? 'selected' : '' }}>
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
                                    <small class="form-text text-muted">Choose a sender ID or use the default</small>
                                </div>

                                <div class="form-group">
                                    <label for="send_option">Send Option</label>
                                    <select class="form-control @error('send_option') is-invalid @enderror" 
                                            id="send_option" name="send_option">
                                        <option value="save_draft" {{ old('send_option', 'save_draft') == 'save_draft' ? 'selected' : '' }}>Save as Draft</option>
                                        <option value="send_now" {{ old('send_option') == 'send_now' ? 'selected' : '' }}>Send Immediately</option>
                                        <option value="schedule" {{ old('send_option') == 'schedule' ? 'selected' : '' }}>Schedule for Later</option>
                                    </select>
                                    @error('send_option')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group schedule-options" style="display: none;">
                                    <label for="scheduled_at">Schedule Date & Time</label>
                                    <input type="datetime-local" 
                                           class="form-control @error('scheduled_at') is-invalid @enderror" 
                                           id="scheduled_at" 
                                           name="scheduled_at" 
                                           value="{{ old('scheduled_at') }}">
                                    @error('scheduled_at')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
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
                                              required>{{ old('message') }}</textarea>
                                    @error('message')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <small class="text-muted">
                                                    Characters: <span id="char-count">0</span>/160
                                                    | SMS Parts: <span id="sms-parts">1</span>
                                                </small>
                                            </div>
                                            <div class="col-md-6 text-right">
                                                <small class="text-muted">
                                                    Estimated Cost: <span id="estimated-cost">0</span> credits
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
                                        <div class="form-group">
                                            <label for="audience_type">Audience Type <span class="text-danger">*</span></label>
                                            <select class="form-control @error('audience_type') is-invalid @enderror" 
                                                    id="audience_type" name="audience_type" required>
                                                <option value="">Select Audience</option>
                                                <option value="all_clients" {{ old('audience_type') == 'all_clients' ? 'selected' : '' }}>All Clients</option>
                                                <option value="active_loans" {{ old('audience_type') == 'active_loans' ? 'selected' : '' }}>Clients with Active Loans</option>
                                                <option value="overdue_loans" {{ old('audience_type') == 'overdue_loans' ? 'selected' : '' }}>Clients with Overdue Loans</option>
                                                <option value="specific_group" {{ old('audience_type') == 'specific_group' ? 'selected' : '' }}>Specific Group</option>
                                                <option value="specific_clients" {{ old('audience_type') == 'specific_clients' ? 'selected' : '' }}>Specific Clients</option>
                                                <option value="custom_filter" {{ old('audience_type') == 'custom_filter' ? 'selected' : '' }}>Custom Filter</option>
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
                                                                    {{ old('group_id') == $group->id ? 'selected' : '' }}>
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
                                                                    {{ in_array($client->id, old('client_ids', [])) ? 'selected' : '' }}>
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
                                                            <option value="active" {{ old('loan_status') == 'active' ? 'selected' : '' }}>Active</option>
                                                            <option value="completed" {{ old('loan_status') == 'completed' ? 'selected' : '' }}>Completed</option>
                                                            <option value="overdue" {{ old('loan_status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                                                            <option value="defaulted" {{ old('loan_status') == 'defaulted' ? 'selected' : '' }}>Defaulted</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="days_overdue">Days Overdue</label>
                                                        <select class="form-control" id="days_overdue" name="days_overdue">
                                                            <option value="">Any</option>
                                                            <option value="1-7" {{ old('days_overdue') == '1-7' ? 'selected' : '' }}>1-7 days</option>
                                                            <option value="8-30" {{ old('days_overdue') == '8-30' ? 'selected' : '' }}>8-30 days</option>
                                                            <option value="31-90" {{ old('days_overdue') == '31-90' ? 'selected' : '' }}>31-90 days</option>
                                                            <option value="90+" {{ old('days_overdue') == '90+' ? 'selected' : '' }}>90+ days</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="client_status">Client Status</label>
                                                        <select class="form-control" id="client_status" name="client_status">
                                                            <option value="">Any Status</option>
                                                            <option value="active" {{ old('client_status') == 'active' ? 'selected' : '' }}>Active</option>
                                                            <option value="inactive" {{ old('client_status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                                            <option value="suspended" {{ old('client_status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
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
                                                <small class="text-muted">Estimated recipients: <span id="recipient-count">0</span></small>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

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
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create Notification
                        </button>
                        <button type="button" class="btn btn-success" id="send-now-btn" style="display: none;">
                            <i class="fas fa-paper-plane"></i> Send Now
                        </button>
                        <a href="{{ route('tenant.sms-notifications.index') }}" class="btn btn-secondary">
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
    // Character count and SMS parts calculation
    $('#message').on('input', function() {
        const message = $(this).val();
        const charCount = message.length;
        const smsParts = Math.ceil(charCount / 160) || 1;
        
        $('#char-count').text(charCount);
        $('#sms-parts').text(smsParts);
        
        // Update estimated cost (assuming 1 credit per SMS part per recipient)
        const recipientCount = parseInt($('#recipient-count').text()) || 0;
        const estimatedCost = smsParts * recipientCount;
        $('#estimated-cost').text(estimatedCost);
        
        // Color coding for character count
        if (charCount > 160) {
            $('#char-count').addClass('text-warning');
        } else {
            $('#char-count').removeClass('text-warning');
        }
    });

    // Show/hide schedule options
    $('#send_option').change(function() {
        const sendOption = $(this).val();
        
        if (sendOption === 'schedule') {
            $('.schedule-options').slideDown();
            $('#scheduled_at').prop('required', true);
        } else {
            $('.schedule-options').slideUp();
            $('#scheduled_at').prop('required', false);
        }
        
        // Show/hide send now button
        if (sendOption === 'send_now') {
            $('#send-now-btn').show();
            $(this).closest('form').find('button[type="submit"]').text('Send Now');
        } else {
            $('#send-now-btn').hide();
            $(this).closest('form').find('button[type="submit"]').text('Create Notification');
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

    // Set minimum datetime for scheduling
    const now = new Date();
    const minDateTime = new Date(now.getTime() + 60 * 60 * 1000); // 1 hour from now
    $('#scheduled_at').attr('min', minDateTime.toISOString().slice(0, 16));

    // Form validation
    $('#createNotificationForm').submit(function(e) {
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

        // Check schedule datetime
        const sendOption = $('#send_option').val();
        if (sendOption === 'schedule') {
            const scheduledAt = new Date($('#scheduled_at').val());
            const now = new Date();
            
            if (scheduledAt <= now) {
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
        $(this).find('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creating...');
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
                count = 0;
        }
        
        $('#recipient-count').text(count);
        
        // Update estimated cost
        const message = $('#message').val();
        const smsParts = Math.ceil(message.length / 160) || 1;
        const estimatedCost = smsParts * count;
        $('#estimated-cost').text(estimatedCost);
    }

    // Initialize
    updateRecipientCount();
});
</script>
@endsection