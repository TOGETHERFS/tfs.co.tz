<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'notification_type_id',
        'channel',
        'subject',
        'body',
        'variables',
        'is_active',
        'locale',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the notification type that owns the template
     */
    public function notificationType(): BelongsTo
    {
        return $this->belongsTo(NotificationType::class);
    }

    /**
     * Get template by notification type key and channel
     */
    public static function getByTypeAndChannel(string $typeKey, string $channel, string $locale = 'en')
    {
        return static::whereHas('notificationType', function ($query) use ($typeKey) {
            $query->where('key', $typeKey)->where('is_active', true);
        })
        ->where('channel', $channel)
        ->where('locale', $locale)
        ->where('is_active', true)
        ->first();
    }

    /**
     * Render template with variables
     */
    public function render(array $data = []): array
    {
        $subject = $this->renderString($this->subject, $data);
        $body = $this->renderString($this->body, $data);

        return [
            'subject' => $subject,
            'body' => $body,
        ];
    }

    /**
     * Render a string template with variables
     */
    private function renderString(?string $template, array $data): ?string
    {
        if (!$template) {
            return null;
        }

        // Replace placeholders like {{variable_name}} with actual values
        return preg_replace_callback('/\{\{(\w+)\}\}/', function ($matches) use ($data) {
            $key = $matches[1];
            return $data[$key] ?? $matches[0]; // Return original if not found
        }, $template);
    }

    /**
     * Get available channels
     */
    public static function getAvailableChannels(): array
    {
        return [
            'database' => 'In-App Notification',
            'mail' => 'Email',
            'sms' => 'SMS',
        ];
    }

    /**
     * Get templates for a notification type
     */
    public static function getForNotificationType(int $notificationTypeId, string $locale = 'en')
    {
        return static::where('notification_type_id', $notificationTypeId)
            ->where('locale', $locale)
            ->where('is_active', true)
            ->get()
            ->keyBy('channel');
    }

    /**
     * Create default templates for a notification type
     */
    public static function createDefaultTemplates(NotificationType $notificationType): void
    {
        $defaultTemplates = [
            'database' => [
                'subject' => $notificationType->name,
                'body' => 'You have a new notification: ' . $notificationType->name,
            ],
            'mail' => [
                'subject' => $notificationType->name . ' - {{app_name}}',
                'body' => "Hello {{user_name}},\n\n" . $notificationType->description . "\n\nBest regards,\n{{app_name}} Team",
            ],
            'sms' => [
                'subject' => null,
                'body' => $notificationType->name . ': {{message}}',
            ],
        ];

        foreach ($notificationType->default_channels as $channel) {
            if (isset($defaultTemplates[$channel])) {
                static::create([
                    'notification_type_id' => $notificationType->id,
                    'channel' => $channel,
                    'subject' => $defaultTemplates[$channel]['subject'],
                    'body' => $defaultTemplates[$channel]['body'],
                    'variables' => ['user_name', 'app_name', 'message'],
                    'is_active' => true,
                    'locale' => 'en',
                ]);
            }
        }
    }
}