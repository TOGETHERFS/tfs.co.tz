<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    public function permissions()
    {
        return $this->belongsToMany(AdminPermission::class, 'admin_role_permission', 'admin_role_id', 'admin_permission_id')
                    ->withTimestamps();
    }

    public function users()
    {
        return $this->hasMany(User::class, 'admin_role_id');
    }

    public function hasPermission($permissionSlug)
    {
        return $this->permissions()->where('slug', $permissionSlug)->exists();
    }

    public function hasAnyPermission($permissionSlugs)
    {
        return $this->permissions()->whereIn('slug', $permissionSlugs)->exists();
    }
}
