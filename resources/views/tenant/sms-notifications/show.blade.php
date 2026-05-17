@extends('layouts.tenant')

@section('title', 'SMS Notification Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">SMS Notification Details</h3>
                    <div class="card-tools">
                        <a href="{{ route('tenant.sms-notifications.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to SMS Notifications
                        </a>
                        @if(isset($smsNotification))
                            <a href="{{ route('tenant.sms-notifications.edit', $smsNotification->id) }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @if(isset($smsNotification))
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Notification Information</h5>
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Notification ID:</strong></td>
                                        <td>{{ $smsNotification->id }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Title:</strong></td>
                                        <td>{{ $smsNotification->title }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Type:</strong></td>
                                        <td>
                                            @switch($smsNotification->type ?? 'general')
                                                @case('loan_reminder')
                                                    <span class="badge badge-warning">Loan Reminder</span>
                                                    @break
                                                @case('payment_due')
                                                    <span class="badge badge-danger">Payment Due</span>
                                                    @break
                                                @case('payment_received')
                                                    <span class="badge badge-success">Payment Received</span>
                                                    @break
                                                @case('account_update')
                                                    <span class="badge badge-info">Account Update</span>
                                                    @break
                                                @case('promotional')
                                                    <span class="badge badge-primary">Promotional</span>
                                                    @break
                                                @default
                                                    <span class="badge badge-secondary">General</span>
                                            @endswitch
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status:</strong></td>
                                        <td>
                                            @switch($smsNotification->status ?? 'draft')
                                                @case('sent')
                                                    <span class="badge badge-success">Sent</span>
                                                    @break
                                                @case('scheduled')
                                                    <span class="badge badge-warning">Scheduled</span>
                                                    @break
                                                @case('failed')
                                                    <span class="badge badge-danger">Failed</span>
                                                    @break
                                                @case('cancelled')
                                                    <span class="badge badge-secondary">Cancelled</span>
                                                    @break
                                                @default
                                                    <span class="badge badge-info">Draft</span>
                                            @endswitch
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Priority:</strong></td>
                                        <td>
                                            @switch($smsNotification->priority ?? 'normal')
                                                @case('high')
                                                    <span class="badge badge-danger">High</span>
                                                    @break
                                                @case('low')
                                                    <span class="badge badge-secondary">Low</span>
                                                    @break
                                                @default
                                                    <span class="badge badge-primary">Normal</span>
                                            @endswitch
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Created Date:</strong></td>
                                        <td>{{ $smsNotification->created_at ? $smsNotification->created_at->format('d M Y H:i') : 'N/A' }}</td>
                                    </tr>
                                    @if($smsNotification->scheduled_at)
                                        <tr>
                                            <td><strong>Scheduled For:</strong></td>
                                            <td>{{ $smsNotification->scheduled_at->format('d M Y H:i') }}</td>
                                        </tr>
                                    @endif
                                    @if($smsNotification->sent_at)
                                        <tr>
                                            <td><strong>Sent At:</strong></td>
                                            <td>{{ $smsNotification->sent_at->format('d M Y H:i') }}</td>
                                        </tr>
                                    @endif
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5>Delivery Information</h5>
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Sender ID:</strong></td>
                                        <td>{{ $smsNotification->sender_id ?? 'Default' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Total Recipients:</strong></td>
                                        <td>{{ number_format($smsNotification->total_recipients ?? 0) }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Successfully Sent:</strong></td>
                                        <td>
                                            <span class="text-success">{{ number_format($smsNotification->sent_count ?? 0) }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Failed:</strong></td>
                                        <td>
                                            <span class="text-danger">{{ number_format($smsNotification->failed_count ?? 0) }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Delivery Rate:</strong></td>
                                        <td>
                                            @php
                                                $total = $smsNotification->total_recipients ?? 0;
                                                $sent = $smsNotification->sent_count ?? 0;
                                                $rate = $total > 0 ? ($sent / $total) * 100 : 0;
                                            @endphp
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-success" role="progressbar" 
                                                     style="width: {{ $rate }}%" 
                                                     aria-valuenow="{{ $rate }}" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                    {{ number_format($rate, 1) }}%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>SMS Credits Used:</strong></td>
                                        <td>{{ number_format($smsNotification->credits_used ?? 0) }}</td>
                                    </tr>
                                    @if($smsNotification->estimated_cost)
                                        <tr>
                                            <td><strong>Estimated Cost:</strong></td>
                                            <td>{{ $smsNotification->currency ?? 'TZS' }} {{ number_format($smsNotification->estimated_cost, 2) }}</td>
                                        </tr>
                                    @endif
                                </table>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <h5>Message Content</h5>
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label><strong>Message:</strong></label>
                                            <div class="border p-3 bg-white rounded">
                                                {{ $smsNotification->message ?? 'No message content' }}
                                            </div>
                                            <small class="form-text text-muted">
                                                Character count: {{ strlen($smsNotification->message ?? '') }} characters
                                                | SMS parts: {{ ceil(strlen($smsNotification->message ?? '') / 160) }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($smsNotification->target_audience)
                            <div class="row mt-3">
                                <div class="col-12">
                                    <h5>Target Audience</h5>
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            @php
                                                $audience = is_string($smsNotification->target_audience) 
                                                    ? json_decode($smsNotification->target_audience, true) 
                                                    : $smsNotification->target_audience;
                                            @endphp
                                            @if(is_array($audience))
                                                <ul class="list-unstyled">
                                                    @foreach($audience as $key => $value)
                                                        <li><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> {{ is_array($value) ? implode(', ', $value) : $value }}</li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <p>{{ $smsNotification->target_audience }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($smsNotification->error_message)
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="alert alert-danger">
                                        <h6><i class="fas fa-exclamation-triangle"></i> Error Details</h6>
                                        <p class="mb-0">{{ $smsNotification->error_message }}</p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="row mt-3">
                            <div class="col-12">
                                <h5>Actions</h5>
                                <div class="btn-group" role="group">
                                    @if($smsNotification->status === 'draft')
                                        <a href="{{ route('tenant.sms-notifications.edit', $smsNotification->id) }}" class="btn btn-primary">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <button type="button" class="btn btn-success" onclick="sendNotification({{ $smsNotification->id }})">
                                            <i class="fas fa-paper-plane"></i> Send Now
                                        </button>
                                        <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#scheduleModal">
                                            <i class="fas fa-clock"></i> Schedule
                                        </button>
                                    @elseif($smsNotification->status === 'scheduled')
                                        <button type="button" class="btn btn-success" onclick="sendNotification({{ $smsNotification->id }})">
                                            <i class="fas fa-paper-plane"></i> Send Now
                                        </button>
                                        <button type="button" class="btn btn-secondary" onclick="cancelNotification({{ $smsNotification->id }})">
                                            <i class="fas fa-times"></i> Cancel
                                        </button>
                                    @elseif($smsNotification->status === 'failed')
                                        <button type="button" class="btn btn-warning" onclick="retryNotification({{ $smsNotification->id }})">
                                            <i class="fas fa-redo"></i> Retry
                                        </button>
                                    @endif
                                    
                                    <a href="{{ route('tenant.sms-notifications.duplicate', $smsNotification->id) }}" class="btn btn-info">
                                        <i class="fas fa-copy"></i> Duplicate
                                    </a>
                                    
                                    @if(in_array($smsNotification->status, ['sent', 'failed']))
                                        <a href="{{ route('tenant.sms-notifications.report', $smsNotification->id) }}" class="btn btn-secondary">
                                            <i class="fas fa-chart-bar"></i> View Report
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Schedule Modal -->
                        <div class="modal fade" id="scheduleModal" tabindex="-1" role="dialog">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <form action="{{ route('tenant.sms-notifications.schedule', $smsNotification->id) }}" method="POST">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Schedule SMS Notification</h5>
                                            <button type="button" class="close" data-dismiss="modal">
                                                <span>&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label for="scheduled_date">Schedule Date</label>
                                                <input type="date" class="form-control" id="scheduled_date" name="scheduled_date" 
                                                       min="{{ date('Y-m-d') }}" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="scheduled_time">Schedule Time</label>
                                                <input type="time" class="form-control" id="scheduled_time" name="scheduled_time" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="timezone">Timezone</label>
                                                <select class="form-control" id="timezone" name="timezone">
                                                    <option value="Africa/Dar_es_Salaam">East Africa Time (EAT)</option>
                                                    <option value="UTC">UTC</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-warning">Schedule</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <h5>SMS Notification Not Found</h5>
                            <p>The requested SMS notification could not be found.</p>
                            <a href="{{ route('tenant.sms-notifications.index') }}" class="btn btn-primary">Back to SMS Notifications</a>
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
function sendNotification(notificationId) {
    if (confirm('Are you sure you want to send this SMS notification now?')) {
        // Create a form and submit it
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/tenant/sms-notifications/${notificationId}/send`;
        
        // Add CSRF token
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        form.appendChild(csrfToken);
        
        document.body.appendChild(form);
        form.submit();
    }
}

function cancelNotification(notificationId) {
    if (confirm('Are you sure you want to cancel this scheduled SMS notification?')) {
        // Create a form and submit it
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/tenant/sms-notifications/${notificationId}/cancel`;
        
        // Add CSRF token
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        form.appendChild(csrfToken);
        
        // Add method
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'PATCH';
        form.appendChild(methodInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

function retryNotification(notificationId) {
    if (confirm('Are you sure you want to retry sending this SMS notification?')) {
        // Create a form and submit it
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/tenant/sms-notifications/${notificationId}/retry`;
        
        // Add CSRF token
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        form.appendChild(csrfToken);
        
        document.body.appendChild(form);
        form.submit();
    }
}

$(document).ready(function() {
    // Set minimum datetime for scheduling
    const now = new Date();
    const tomorrow = new Date(now.getTime() + 24 * 60 * 60 * 1000);
    
    $('#scheduled_date').attr('min', tomorrow.toISOString().split('T')[0]);
    
    // Default to current time + 1 hour
    const defaultTime = new Date(now.getTime() + 60 * 60 * 1000);
    $('#scheduled_time').val(defaultTime.toTimeString().slice(0, 5));
});
</script>
@endsection