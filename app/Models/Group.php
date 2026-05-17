<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Group extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'branch_name',
        'loan_officer',
        'meeting_area',
        'bank_account',
        'region',
        'ward',
        'village',
        'box_number',
        'phone',
        'description',
        'status',
    ];

    /**
     * Clients belonging to the group.
     */
    public function clients()
    {
        return $this->belongsToMany(Client::class, 'group_client');
    }

    /**
     * Users attached to the group.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_user');
    }
}