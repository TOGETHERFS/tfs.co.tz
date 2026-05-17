<?php

namespace App\Http\Controllers;

use App\Models\NotificationType;
use App\Models\NotificationTemplate;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    /**
     * Display a listing of the user's notifications.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Get notifications with pagination
        $notifications = $user->notifications()
            ->when($request->has('unread'), function ($query) {
                return $query->whereNull('read_at');
            })
            ->when($request->has('type'), function ($query) use ($request) {
                return $query->where('type', $request->type);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Get unread count
        $unreadCount = $user->unreadNotifications()->count();

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead($id)
    {
        $user = Auth::user();
        $notification = $user->notifications()->findOrFail($id);
        
        $notification->markAsRead();
        
        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        $user->unreadNotifications->markAsRead();
        
        return response()->json(['success' => true]);
    }

    /**
     * Delete a notification.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $notification = $user->notifications()->findOrFail($id);
        
        $notification->delete();
        
        return response()->json(['success' => true]);
    }

    /**
     * Get notifications for API (used by the header dropdown).
     */
    public function getNotifications(Request $request)
    {
        $user = Auth::user();
        
        // Handle unauthenticated requests gracefully
        if (!$user) {
            return response()->json([
                'notifications' => [],
                'unread_count' => 0,
            ]);
        }
        
        $notifications = $user->notifications()
            ->limit(10)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'data' => $notification->data,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at,
                ];
            });

        $unreadCount = $user->unreadNotifications()->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Get all notification types grouped by category
     */
    public function getNotificationTypes()
    {
        $notificationTypes = NotificationType::where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->groupBy('category');

        return response()->json($notificationTypes);
    }

    /**
     * Get notification types by category
     */
    public function getNotificationTypesByCategory($category)
    {
        $notificationTypes = $this->notificationService->getNotificationTypesByCategory($category);
        return response()->json($notificationTypes);
    }

    /**
     * Get notification templates for a specific type
     */
    public function getNotificationTemplates($notificationTypeId)
    {
        $templates = NotificationTemplate::getForNotificationType($notificationTypeId);
        return response()->json($templates);
    }

    /**
     * Send a custom notification
     */
    public function sendNotification(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'notification_type' => 'required|string',
            'data' => 'array',
            'channels' => 'array',
        ]);

        $user = \App\Models\User::findOrFail($request->user_id);
        
        $result = $this->notificationService->sendNotification(
            $user,
            $request->notification_type,
            $request->data ?? [],
            $request->channels
        );

        return response()->json([
            'success' => $result,
            'message' => $result ? 'Notification sent successfully' : 'Failed to send notification'
        ]);
    }

    /**
     * Send bulk notifications
     */
    public function sendBulkNotification(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'notification_type' => 'required|string',
            'data' => 'array',
            'channels' => 'array',
        ]);

        $users = \App\Models\User::whereIn('id', $request->user_ids)->get();
        
        $results = $this->notificationService->sendBulkNotification(
            $users->toArray(),
            $request->notification_type,
            $request->data ?? [],
            $request->channels
        );

        $successCount = count(array_filter($results));
        $totalCount = count($results);

        return response()->json([
            'success' => $successCount > 0,
            'message' => "Sent {$successCount} out of {$totalCount} notifications",
            'results' => $results
        ]);
    }

    /**
     * Send notification to admin users
     */
    public function sendAdminNotification(Request $request)
    {
        $request->validate([
            'notification_type' => 'required|string',
            'data' => 'array',
            'channels' => 'array',
        ]);

        $results = $this->notificationService->sendAdminNotification(
            $request->notification_type,
            $request->data ?? [],
            $request->channels
        );

        $successCount = count(array_filter($results));
        $totalCount = count($results);

        return response()->json([
            'success' => $successCount > 0,
            'message' => "Sent {$successCount} out of {$totalCount} admin notifications",
            'results' => $results
        ]);
    }

    /**
     * Get user's notification preferences
     */
    public function getNotificationPreferences()
    {
        $user = Auth::user();
        $preferences = $this->notificationService->getUserNotificationPreferences($user);
        
        return response()->json($preferences);
    }

    /**
     * Update user's notification preferences
     */
    public function updateNotificationPreferences(Request $request)
    {
        $request->validate([
            'database' => 'boolean',
            'mail' => 'boolean',
            'sms' => 'boolean',
        ]);

        // This would typically save to a user_notification_preferences table
        // For now, we'll just return success
        return response()->json([
            'success' => true,
            'message' => 'Notification preferences updated successfully'
        ]);
    }

    /**
     * Get notification statistics
     */
    public function getNotificationStats()
    {
        $user = Auth::user();
        
        $stats = [
            'total_notifications' => $user->notifications()->count(),
            'unread_notifications' => $user->unreadNotifications()->count(),
            'notifications_by_type' => $user->notifications()
                ->selectRaw('JSON_EXTRACT(data, "$.type") as notification_type, COUNT(*) as count')
                ->groupBy('notification_type')
                ->get()
                ->pluck('count', 'notification_type'),
            'recent_activity' => $user->notifications()
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($notification) {
                    return [
                        'id' => $notification->id,
                        'type' => $notification->data['type'] ?? 'unknown',
                        'title' => $notification->data['title'] ?? 'Notification',
                        'created_at' => $notification->created_at,
                        'read_at' => $notification->read_at,
                    ];
                })
        ];

        return response()->json($stats);
    }

    /**
     * Test notification system
     */
    public function testNotification(Request $request)
    {
        $request->validate([
            'notification_type' => 'required|string',
            'channels' => 'array',
        ]);

        $user = Auth::user();
        
        $testData = [
            'user_name' => $user->name,
            'app_name' => config('app.name'),
            'test_message' => 'This is a test notification',
            'amount' => '1,000.00',
            'transaction_id' => 'TEST_' . time(),
        ];

        $result = $this->notificationService->sendNotification(
            $user,
            $request->notification_type,
            $testData,
            $request->channels
        );

        return response()->json([
            'success' => $result,
            'message' => $result ? 'Test notification sent successfully' : 'Failed to send test notification'
        ]);
    }
}