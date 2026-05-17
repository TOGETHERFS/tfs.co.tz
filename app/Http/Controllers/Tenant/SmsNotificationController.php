<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\SmsWallet;
use App\Services\SmsNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SmsNotificationController extends Controller
{
    protected $notificationService;

    public function __construct(SmsNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display notification preferences.
     */
    public function index()
    {
        $tenant = Auth::user()->tenant;
        $wallet = $tenant->smsWallet;

        if (!$wallet) {
            return redirect()->route('sms-wallet.index')
                ->with('error', 'SMS wallet not found. Please set up your SMS wallet first.');
        }

        return view('tenant.sms-notifications.index', compact('wallet'));
    }

    /**
     * Update notification preferences.
     */
    public function update(Request $request)
    {
        $tenant = Auth::user()->tenant;
        $wallet = $tenant->smsWallet;

        if (!$wallet) {
            return redirect()->route('sms-wallet.index')
                ->with('error', 'SMS wallet not found.');
        }

        $validator = Validator::make($request->all(), [
            'low_balance_notifications' => 'boolean',
            'email_notifications_enabled' => 'boolean',
            'daily_summary_enabled' => 'boolean',
            'weekly_report_enabled' => 'boolean',
            'low_balance_threshold' => 'required|integer|min:0|max:100000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $wallet->update([
            'low_balance_notifications' => $request->boolean('low_balance_notifications'),
            'email_notifications_enabled' => $request->boolean('email_notifications_enabled'),
            'daily_summary_enabled' => $request->boolean('daily_summary_enabled'),
            'weekly_report_enabled' => $request->boolean('weekly_report_enabled'),
            'low_balance_threshold' => $request->input('low_balance_threshold'),
        ]);

        return redirect()->back()
            ->with('success', 'Notification preferences updated successfully.');
    }

    /**
     * Test notification by sending a sample notification.
     */
    public function test(Request $request)
    {
        $tenant = Auth::user()->tenant;
        $wallet = $tenant->smsWallet;

        if (!$wallet) {
            return response()->json(['error' => 'SMS wallet not found.'], 404);
        }

        $type = $request->input('type', 'low_balance');

        try {
            switch ($type) {
                case 'low_balance':
                    $this->notificationService->sendLowBalanceNotification($wallet);
                    $message = 'Low balance test notification sent successfully.';
                    break;

                default:
                    return response()->json(['error' => 'Invalid notification type.'], 400);
            }

            return response()->json(['success' => true, 'message' => $message]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to send test notification: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get notification history.
     */
    public function history(Request $request)
    {
        $user = Auth::user();
        
        $notifications = $user->notifications()
            ->where(function ($query) {
                $query->where('type', 'like', '%Sms%')
                    ->orWhere('data->type', 'like', '%sms%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        if ($request->ajax()) {
            return response()->json([
                'notifications' => $notifications->items(),
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                ]
            ]);
        }

        return view('tenant.sms-notifications.history', compact('notifications'));
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(Request $request, $notificationId)
    {
        $user = Auth::user();
        
        $notification = $user->notifications()->find($notificationId);
        
        if (!$notification) {
            return response()->json(['error' => 'Notification not found.'], 404);
        }

        $notification->markAsRead();

        return response()->json(['success' => true, 'message' => 'Notification marked as read.']);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        $user = Auth::user();
        
        $user->unreadNotifications()
            ->where(function ($query) {
                $query->where('type', 'like', '%Sms%')
                    ->orWhere('data->type', 'like', '%sms%');
            })
            ->update(['read_at' => now()]);

        return response()->json(['success' => true, 'message' => 'All SMS notifications marked as read.']);
    }

    /**
     * Delete notification.
     */
    public function delete(Request $request, $notificationId)
    {
        $user = Auth::user();
        
        $notification = $user->notifications()->find($notificationId);
        
        if (!$notification) {
            return response()->json(['error' => 'Notification not found.'], 404);
        }

        $notification->delete();

        return response()->json(['success' => true, 'message' => 'Notification deleted successfully.']);
    }

    /**
     * Get unread notification count.
     */
    public function unreadCount(Request $request)
    {
        $user = Auth::user();
        
        $count = $user->unreadNotifications()
            ->where(function ($query) {
                $query->where('type', 'like', '%Sms%')
                    ->orWhere('data->type', 'like', '%sms%');
            })
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Get recent notifications for dropdown.
     */
    public function recent(Request $request)
    {
        $user = Auth::user();
        
        $notifications = $user->notifications()
            ->where(function ($query) {
                $query->where('type', 'like', '%Sms%')
                    ->orWhere('data->type', 'like', '%sms%');
            })
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json(['notifications' => $notifications]);
    }
}