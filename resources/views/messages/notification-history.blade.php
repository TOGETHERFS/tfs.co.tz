@extends('layouts.user')

@section('title', 'SMS Notification History')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">SMS Notification History</h1>
        <div class="flex space-x-3">
            <a href="{{ route('sms-notifications.preferences') }}" 
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                Notification Settings
            </a>
            <a href="{{ route('messages.index') }}" 
               class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                Back to Messages
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
    @endif

    <!-- Filter and Actions -->
    <div class="mb-6 bg-white shadow rounded-lg p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
            <div class="flex flex-col sm:flex-row sm:items-center space-y-4 sm:space-y-0 sm:space-x-4">
                <!-- Filter Form -->
                <form method="GET" action="{{ route('sms-notifications.history') }}" class="flex flex-col sm:flex-row sm:items-center space-y-4 sm:space-y-0 sm:space-x-4">
                    <div>
                        <select name="type" class="border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All Types</option>
                            <option value="low_balance" {{ request('type') === 'low_balance' ? 'selected' : '' }}>Low Balance</option>
                            <option value="topup" {{ request('type') === 'topup' ? 'selected' : '' }}>Top-up</option>
                            <option value="sender_id" {{ request('type') === 'sender_id' ? 'selected' : '' }}>Sender ID</option>
                            <option value="usage_summary" {{ request('type') === 'usage_summary' ? 'selected' : '' }}>Usage Summary</option>
                        </select>
                    </div>
                    <div>
                        <select name="read_status" class="border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All Status</option>
                            <option value="unread" {{ request('read_status') === 'unread' ? 'selected' : '' }}>Unread</option>
                            <option value="read" {{ request('read_status') === 'read' ? 'selected' : '' }}>Read</option>
                        </select>
                    </div>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        Filter
                    </button>
                    @if(request()->hasAny(['type', 'read_status']))
                        <a href="{{ route('sms-notifications.history') }}" class="text-gray-600 hover:text-gray-800 text-sm">
                            Clear Filters
                        </a>
                    @endif
                </form>
            </div>

            <!-- Actions -->
            <div class="flex space-x-3">
                @if($unreadCount > 0)
                    <button onclick="markAllAsRead()" 
                            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        Mark All as Read ({{ $unreadCount }})
                    </button>
                @endif
                <button onclick="deleteSelected()" 
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                    Delete Selected
                </button>
            </div>
        </div>
    </div>

    <!-- Notifications List -->
    <div class="bg-white shadow rounded-lg">
        @if($notifications->count() > 0)
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center">
                    <input type="checkbox" id="select-all" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <label for="select-all" class="ml-2 text-sm text-gray-600">Select All</label>
                    <span class="ml-4 text-sm text-gray-500">{{ $notifications->total() }} total notifications</span>
                </div>
            </div>
            
            <div class="divide-y divide-gray-200">
                @foreach($notifications as $notification)
                    <div class="p-6 {{ $notification->read_at ? 'bg-white' : 'bg-blue-50' }} hover:bg-gray-50 transition-colors">
                        <div class="flex items-start space-x-4">
                            <input type="checkbox" name="selected_notifications[]" value="{{ $notification->id }}" 
                                   class="mt-1 rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 notification-checkbox">
                            
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h3 class="text-sm font-medium text-gray-900">
                                            {{ $notification->data['title'] ?? 'SMS Notification' }}
                                            @if(!$notification->read_at)
                                                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    New
                                                </span>
                                            @endif
                                        </h3>
                                        <p class="mt-1 text-sm text-gray-600">
                                            {{ $notification->data['message'] ?? $notification->data['body'] ?? 'No message content' }}
                                        </p>
                                        
                                        @if(isset($notification->data['action_url']))
                                            <div class="mt-2">
                                                <a href="{{ $notification->data['action_url'] }}" 
                                                   class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                                    {{ $notification->data['action_text'] ?? 'View Details' }} →
                                                </a>
                                            </div>
                                        @endif
                                        
                                        <div class="mt-2 flex items-center space-x-4 text-xs text-gray-500">
                                            <span>{{ $notification->created_at->format('M j, Y g:i A') }}</span>
                                            @if($notification->read_at)
                                                <span>Read {{ $notification->read_at->diffForHumans() }}</span>
                                            @endif
                                            <span class="capitalize">{{ $notification->data['type'] ?? 'general' }}</span>
                                        </div>
                                    </div>
                                    
                                    <div class="ml-4 flex-shrink-0 flex space-x-2">
                                        @if(!$notification->read_at)
                                            <button onclick="markAsRead('{{ $notification->id }}')" 
                                                    class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                                Mark as Read
                                            </button>
                                        @endif
                                        <button onclick="deleteNotification('{{ $notification->id }}')" 
                                                class="text-red-600 hover:text-red-800 text-sm font-medium">
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $notifications->withQueryString()->links() }}
            </div>
        @else
            <div class="px-6 py-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM9 7H4l5-5v5zM12 12l8-8m0 8l-8-8" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No notifications found</h3>
                <p class="mt-1 text-sm text-gray-500">
                    @if(request()->hasAny(['type', 'read_status']))
                        No notifications match your current filters.
                    @else
                        You haven't received any SMS notifications yet.
                    @endif
                </p>
                @if(request()->hasAny(['type', 'read_status']))
                    <div class="mt-6">
                        <a href="{{ route('sms-notifications.history') }}" 
                           class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            Clear Filters
                        </a>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>

<script>
// Select all functionality
document.getElementById('select-all').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.notification-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});

// Mark single notification as read
function markAsRead(notificationId) {
    fetch(`{{ route('sms-notifications.mark-read', '') }}/${notificationId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to mark notification as read');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
    });
}

// Mark all notifications as read
function markAllAsRead() {
    if (confirm('Mark all notifications as read?')) {
        fetch('{{ route("sms-notifications.mark-all-read") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to mark notifications as read');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
        });
    }
}

// Delete single notification
function deleteNotification(notificationId) {
    if (confirm('Delete this notification?')) {
        fetch(`{{ route('sms-notifications.delete', '') }}/${notificationId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to delete notification');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
        });
    }
}

// Delete selected notifications
function deleteSelected() {
    const selected = Array.from(document.querySelectorAll('.notification-checkbox:checked')).map(cb => cb.value);
    
    if (selected.length === 0) {
        alert('Please select notifications to delete');
        return;
    }
    
    if (confirm(`Delete ${selected.length} selected notification(s)?`)) {
        fetch('{{ route("sms-notifications.delete-selected") }}', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ notification_ids: selected })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to delete notifications');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
        });
    }
}
</script>
@endsection