@extends('layouts.user')

@section('title', 'SMS Notification Preferences')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">SMS Notification Preferences</h1>
        <a href="{{ route('messages.index') }}" 
           class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
            Back to Messages
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white shadow rounded-lg">
        <form action="{{ route('sms-notifications.preferences.update') }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Notification Settings</h3>
                <p class="mt-1 text-sm text-gray-600">
                    Configure how you want to receive SMS-related notifications.
                </p>
            </div>

            <div class="px-6 py-4 space-y-6">
                <!-- Email Notifications Toggle -->
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <label class="text-base font-medium text-gray-900">Email Notifications</label>
                        <p class="text-sm text-gray-600">Receive notifications via email in addition to in-app notifications</p>
                    </div>
                    <div class="ml-4">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="email_notifications_enabled" value="1" 
                                   {{ old('email_notifications_enabled', $wallet->email_notifications_enabled) ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                </div>

                <!-- Low Balance Notifications -->
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <label class="text-base font-medium text-gray-900">Low Balance Alerts</label>
                        <p class="text-sm text-gray-600">Get notified when your SMS credits are running low</p>
                    </div>
                    <div class="ml-4">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="low_balance_notifications" value="1" 
                                   {{ old('low_balance_notifications', $wallet->low_balance_notifications) ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                </div>

                <!-- Top-up Notifications -->
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <label class="text-base font-medium text-gray-900">Top-up Notifications</label>
                        <p class="text-sm text-gray-600">Get notified about successful and failed top-up transactions</p>
                    </div>
                    <div class="ml-4">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="topup_notifications" value="1" 
                                   {{ old('topup_notifications', $wallet->topup_notifications) ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                </div>

                <!-- Sender ID Notifications -->
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <label class="text-base font-medium text-gray-900">Sender ID Status Updates</label>
                        <p class="text-sm text-gray-600">Get notified about changes to your sender ID applications</p>
                    </div>
                    <div class="ml-4">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="sender_id_notifications" value="1" 
                                   {{ old('sender_id_notifications', $wallet->sender_id_notifications) ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                </div>

                <!-- Daily Usage Notifications -->
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <label class="text-base font-medium text-gray-900">Daily Usage Summary</label>
                        <p class="text-sm text-gray-600">Receive daily reports of your SMS usage and remaining balance</p>
                    </div>
                    <div class="ml-4">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="daily_usage_notifications" value="1" 
                                   {{ old('daily_usage_notifications', $wallet->daily_usage_notifications) ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                </div>

                <!-- Weekly Usage Notifications -->
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <label class="text-base font-medium text-gray-900">Weekly Usage Report</label>
                        <p class="text-sm text-gray-600">Receive weekly analytics and usage trends for your SMS campaigns</p>
                    </div>
                    <div class="ml-4">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="weekly_usage_notifications" value="1" 
                                   {{ old('weekly_usage_notifications', $wallet->weekly_usage_notifications) ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-between items-center">
                <div class="flex space-x-3">
                    <button type="submit" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                        Save Preferences
                    </button>
                    <button type="button" onclick="sendTestNotification()" 
                            class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                        Send Test Notification
                    </button>
                </div>
                <div class="text-sm text-gray-600">
                    Last updated: {{ $wallet->updated_at->format('M j, Y g:i A') }}
                </div>
            </div>
        </form>
    </div>

    <!-- Notification History -->
    <div class="mt-8 bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Recent SMS Notifications</h3>
            <p class="mt-1 text-sm text-gray-600">
                Your recent SMS-related notifications and alerts.
            </p>
        </div>
        
        <div id="notification-history" class="divide-y divide-gray-200">
            <!-- Notifications will be loaded here via AJAX -->
            <div class="px-6 py-4 text-center text-gray-500">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mx-auto"></div>
                <p class="mt-2">Loading notifications...</p>
            </div>
        </div>
        
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
            <a href="{{ route('sms-notifications.history') }}" 
               class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                View All Notifications →
            </a>
        </div>
    </div>
</div>

<script>
function sendTestNotification() {
    if (confirm('Send a test notification to verify your settings?')) {
        fetch('{{ route("sms-notifications.test") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Test notification sent successfully!');
                loadNotificationHistory();
            } else {
                alert('Failed to send test notification: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while sending the test notification.');
        });
    }
}

function loadNotificationHistory() {
    fetch('{{ route("sms-notifications.recent") }}')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('notification-history');
            if (data.notifications && data.notifications.length > 0) {
                container.innerHTML = data.notifications.map(notification => `
                    <div class="px-6 py-4 ${notification.read_at ? 'bg-white' : 'bg-blue-50'}">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h4 class="text-sm font-medium text-gray-900">${notification.data.title || 'SMS Notification'}</h4>
                                <p class="text-sm text-gray-600 mt-1">${notification.data.message || notification.data.body}</p>
                                <p class="text-xs text-gray-500 mt-2">${new Date(notification.created_at).toLocaleString()}</p>
                            </div>
                            ${!notification.read_at ? '<div class="ml-4"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">New</span></div>' : ''}
                        </div>
                    </div>
                `).join('');
            } else {
                container.innerHTML = `
                    <div class="px-6 py-8 text-center text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM9 7H4l5-5v5zM12 12l8-8m0 8l-8-8" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No notifications</h3>
                        <p class="mt-1 text-sm text-gray-500">You haven't received any SMS notifications yet.</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
            document.getElementById('notification-history').innerHTML = `
                <div class="px-6 py-4 text-center text-red-500">
                    <p>Failed to load notifications. Please refresh the page.</p>
                </div>
            `;
        });
}

// Load notification history when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadNotificationHistory();
});
</script>
@endsection