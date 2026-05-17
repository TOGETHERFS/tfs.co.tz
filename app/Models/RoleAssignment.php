<?php

namespace App\Models;

use App\Notifications\RoleAssignmentNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleAssignment extends BaseModel
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'role_id',
        'requested_by',
        'approved_by',
        'status',
        'reason',
        'rejection_reason',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    // Methods
    public function approve(User $approver, $reason = null)
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);

        // Assign the role to the user
        $this->user->roles()->syncWithoutDetaching([$this->role_id => [
            'tenant_id' => $this->tenant_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]]);

        // Send notification to the user about the approval
        $this->user->notify(new RoleAssignmentNotification($this, 'approved'));
        
        // Send notification to the requester if different from the user
        if ($this->requested_by !== $this->user_id) {
            $this->requestedBy->notify(new RoleAssignmentNotification($this, 'approved'));
        }
    }

    public function reject(User $approver, $reason)
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);

        // Send notification to the user about the rejection
        $this->user->notify(new RoleAssignmentNotification($this, 'rejected'));
        
        // Send notification to the requester if different from the user
        if ($this->requested_by !== $this->user_id) {
            $this->requestedBy->notify(new RoleAssignmentNotification($this, 'rejected'));
        }
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
