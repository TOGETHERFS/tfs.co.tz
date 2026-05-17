<?php

namespace App\Notifications;

use App\Models\UserRoleAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RoleAssignmentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $assignment;
    protected $type; // 'requested', 'approved', 'rejected'

    /**
     * Create a new notification instance.
     */
    public function __construct(UserRoleAssignment $assignment, string $type)
    {
        $this->assignment = $assignment;
        $this->type = $type;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        $channels = ['database'];
        
        if ($notifiable->email) {
            $channels[] = 'mail';
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $mailMessage = (new MailMessage)
            ->subject($this->getEmailSubject())
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line($this->getEmailMessage());

        if ($this->type === 'requested') {
            $mailMessage->action('Review Assignment', route('user.roles.pending'));
        } else {
            $mailMessage->action('View Roles', route('user.roles.index'));
        }

        return $mailMessage->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'role_assignment',
            'assignment_type' => $this->type,
            'title' => $this->getNotificationTitle(),
            'message' => $this->getNotificationMessage(),
            'assignment_id' => $this->assignment->id,
            'user_id' => $this->assignment->user_id,
            'role_name' => $this->assignment->role->name,
            'requested_by' => $this->assignment->requestedBy->name,
            'action_url' => $this->getActionUrl(),
        ];
    }

    /**
     * Get notification title.
     */
    private function getNotificationTitle(): string
    {
        return match ($this->type) {
            'requested' => 'New Role Assignment Request',
            'approved' => 'Role Assignment Approved',
            'rejected' => 'Role Assignment Rejected',
            default => 'Role Assignment Update',
        };
    }

    /**
     * Get notification message.
     */
    private function getNotificationMessage(): string
    {
        $roleName = $this->assignment->role->name;
        $userName = $this->assignment->user->name;
        $requesterName = $this->assignment->requestedBy->name;

        return match ($this->type) {
            'requested' => "A new role assignment request for '{$roleName}' role has been submitted for {$userName} by {$requesterName}.",
            'approved' => "Your role assignment request for '{$roleName}' role has been approved.",
            'rejected' => "Your role assignment request for '{$roleName}' role has been rejected.",
            default => "Role assignment update for '{$roleName}' role.",
        };
    }

    /**
     * Get email subject.
     */
    private function getEmailSubject(): string
    {
        return match ($this->type) {
            'requested' => 'New Role Assignment Request - Action Required',
            'approved' => 'Role Assignment Approved',
            'rejected' => 'Role Assignment Rejected',
            default => 'Role Assignment Update',
        };
    }

    /**
     * Get email message.
     */
    private function getEmailMessage(): string
    {
        $roleName = $this->assignment->role->name;
        $userName = $this->assignment->user->name;
        $requesterName = $this->assignment->requestedBy->name;

        return match ($this->type) {
            'requested' => "A new role assignment request has been submitted for {$userName} to receive the '{$roleName}' role by {$requesterName}. Please review and approve or reject this request.",
            'approved' => "Your request to assign the '{$roleName}' role has been approved. You now have access to the features and permissions associated with this role.",
            'rejected' => "Your request to assign the '{$roleName}' role has been rejected. If you have questions about this decision, please contact your administrator.",
            default => "There has been an update to your role assignment request for the '{$roleName}' role.",
        };
    }

    /**
     * Get action URL.
     */
    private function getActionUrl(): string
    {
        return match ($this->type) {
            'requested' => route('user.roles.pending'),
            'approved', 'rejected' => route('user.roles.index'),
            default => route('user.roles.index'),
        };
    }

    /**
     * Get the notification's database type.
     */
    public function databaseType($notifiable): string
    {
        return 'role_assignment';
    }
}