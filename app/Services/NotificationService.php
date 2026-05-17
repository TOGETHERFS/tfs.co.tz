<?php

namespace App\Services;

use App\Models\NotificationType;
use App\Models\NotificationTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Notifications\CustomNotification;

class NotificationService
{
    /**
     * Send a notification to a user
     */
    public function sendNotification(
        User $user,
        string $notificationTypeKey,
        array $data = [],
        array $channels = null
    ): bool {
        try {
            $notificationType = NotificationType::where('key', $notificationTypeKey)->first();
            
            if (!$notificationType || !$notificationType->is_active) {
                Log::warning("Notification type '{$notificationTypeKey}' not found or inactive");
                return false;
            }

            // Use provided channels or default channels
            $channels = $channels ?? $notificationType->default_channels;

            foreach ($channels as $channel) {
                $this->sendNotificationViaChannel($user, $notificationType, $channel, $data);
            }

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send notification via specific channel
     */
    private function sendNotificationViaChannel(
        User $user,
        NotificationType $notificationType,
        string $channel,
        array $data
    ): void {
        $template = NotificationTemplate::where('notification_type_id', $notificationType->id)
            ->where('channel', $channel)
            ->where('is_active', true)
            ->first();

        if (!$template) {
            Log::warning("No template found for notification type '{$notificationType->key}' and channel '{$channel}'");
            return;
        }

        $renderedTemplate = $this->renderTemplate($template, $data);

        switch ($channel) {
            case 'database':
                $this->sendDatabaseNotification($user, $notificationType, $renderedTemplate);
                break;
            case 'mail':
                $this->sendEmailNotification($user, $notificationType, $renderedTemplate);
                break;
            case 'sms':
                $this->sendSmsNotification($user, $notificationType, $renderedTemplate);
                break;
            default:
                Log::warning("Unsupported notification channel: {$channel}");
        }
    }

    /**
     * Render template with data
     */
    private function renderTemplate(NotificationTemplate $template, array $data): array
    {
        $subject = $template->subject;
        $body = $template->body;

        // Replace template variables with actual data
        foreach ($data as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $subject = str_replace($placeholder, $value, $subject);
            $body = str_replace($placeholder, $value, $body);
        }

        return [
            'subject' => $subject,
            'body' => $body,
        ];
    }

    /**
     * Send database notification
     */
    private function sendDatabaseNotification(
        User $user,
        NotificationType $notificationType,
        array $renderedTemplate
    ): void {
        $user->notifications()->create([
            'id' => \Str::uuid(),
            'type' => CustomNotification::class,
            'data' => [
                'title' => $renderedTemplate['subject'],
                'message' => $renderedTemplate['body'],
                'type' => $notificationType->key,
                'icon' => $notificationType->icon,
                'color' => $notificationType->color,
                'priority' => $notificationType->priority,
            ],
            'read_at' => null,
        ]);
    }

    /**
     * Send email notification
     */
    private function sendEmailNotification(
        User $user,
        NotificationType $notificationType,
        array $renderedTemplate
    ): void {
        try {
            $adminEmail = config('services.admin_email', env('ADMIN_EMAIL'));
            $notifyAdminAll = (bool) config('services.notify_admin_all', true);
            $fromAddress = env('MAIL_FROM_ADDRESS');
            $fromName = env('MAIL_FROM_NAME', config('app.name'));

            Mail::send([], [], function ($message) use ($user, $renderedTemplate, $adminEmail, $notifyAdminAll, $fromAddress, $fromName) {
                if ($fromAddress) {
                    $message->from($fromAddress, $fromName);
                }
                $message->to($user->email)
                    ->subject($renderedTemplate['subject'])
                    ->html($renderedTemplate['body']);

                if ($notifyAdminAll && $adminEmail) {
                    $message->bcc($adminEmail);
                }
            });
        } catch (\Exception $e) {
            Log::error("Failed to send email notification: " . $e->getMessage());
        }
    }

    private function sendSmsNotification(
        User $user,
        NotificationType $notificationType,
        array $renderedTemplate
    ): void {
        try {
            // Implement SMS sending logic here
            // This could integrate with services like Twilio, Nexmo, etc.
            Log::info("SMS notification sent to {$user->phone}: " . $renderedTemplate['body']);

            // Email a copy of SMS notification to admin via Gmail SMTP
            $adminEmail = config('services.admin_email', env('ADMIN_EMAIL'));
            $fromAddress = env('MAIL_FROM_ADDRESS');
            $fromName = env('MAIL_FROM_NAME', config('app.name'));
            if ($adminEmail) {
                $subject = 'SMS Notification Sent to ' . ($user->name ?? $user->email);
                $body = '<p>An SMS notification was sent.</p>' .
                        '<p><strong>Recipient:</strong> ' . e($user->name ?? $user->email) . ' (' . e($user->phone ?? 'N/A') . ')</p>' .
                        '<hr>' . $renderedTemplate['body'];
                Mail::send([], [], function ($message) use ($adminEmail, $fromAddress, $fromName, $subject, $body) {
                    if ($fromAddress) {
                        $message->from($fromAddress, $fromName);
                    }
                    $message->to($adminEmail)
                        ->subject($subject)
                        ->html($body);
                });
            }
        } catch (\Exception $e) {
            Log::error("Failed to send SMS notification: " . $e->getMessage());
        }
    }

    /**
     * Send bulk notifications to multiple users
     */
    public function sendBulkNotification(
        array $users,
        string $notificationTypeKey,
        array $data = [],
        array $channels = null
    ): array {
        $results = [];
        
        foreach ($users as $user) {
            $results[$user->id] = $this->sendNotification($user, $notificationTypeKey, $data, $channels);
        }

        return $results;
    }

    /**
     * Send notification to admin users
     */
    public function sendAdminNotification(
        string $notificationTypeKey,
        array $data = [],
        array $channels = null
    ): array {
        $adminUsers = User::whereHas('roles', function ($query) {
            $query->where('name', 'admin');
        })->get();

        return $this->sendBulkNotification($adminUsers, $notificationTypeKey, $data, $channels);
    }

    /**
     * Get notification types by category
     */
    public function getNotificationTypesByCategory(string $category): \Illuminate\Database\Eloquent\Collection
    {
        return NotificationType::where('category', $category)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get user's notification preferences
     */
    public function getUserNotificationPreferences(User $user): array
    {
        // This could be extended to store user preferences in a separate table
        return [
            'database' => true,
            'mail' => true,
            'sms' => $user->phone ? true : false,
        ];
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(User $user, string $notificationId): bool
    {
        try {
            $notification = $user->notifications()->where('id', $notificationId)->first();
            
            if ($notification) {
                $notification->markAsRead();
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error("Failed to mark notification as read: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead(User $user): bool
    {
        try {
            $user->unreadNotifications->markAsRead();
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to mark all notifications as read: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get unread notification count for a user
     */
    public function getUnreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    /**
     * Delete old notifications
     */
    public function cleanupOldNotifications(int $daysOld = 30): int
    {
        try {
            $cutoffDate = now()->subDays($daysOld);
            
            return \DB::table('notifications')
                ->where('created_at', '<', $cutoffDate)
                ->delete();
        } catch (\Exception $e) {
            Log::error("Failed to cleanup old notifications: " . $e->getMessage());
            return 0;
        }
    }
}