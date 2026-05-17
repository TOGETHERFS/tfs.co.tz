<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends BaseModel
{
    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'category',
        'is_system',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission')
            ->withTimestamps()
            ->wherePivot('tenant_id', session('tenant_id'));
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_role')
            ->withTimestamps()
            ->wherePivot('tenant_id', session('tenant_id'));
    }
}