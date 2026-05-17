<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'phone',
        'password',
        'role',
        'position',
        'branch_id',
        'admin_role_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (session()->has('tenant_id') && !$model->tenant_id) {
                $model->tenant_id = session('tenant_id');
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function repayments()
    {
        return $this->hasMany(Repayment::class);
    }

    public function isAdmin()
    {
        $adminAliases = ['admin', 'administrator'];
        $superAliases = ['super_admin', 'superadmin', 'super-admin', 'super admin'];
        $roleLower = strtolower($this->role);
        
        $isSuper = in_array($roleLower, $superAliases)
            || $this->hasRole('super_admin')
            || $this->hasRole('superadmin')
            || $this->hasRole('super-admin')
            || $this->hasRole('super admin');

        return in_array($roleLower, $adminAliases)
            || $this->hasRole('admin')
            || $isSuper;
    }

    public function isSuperAdmin(): bool
    {
        $superAliases = ['super_admin', 'superadmin', 'super-admin', 'super admin'];
        $roleLower = strtolower($this->role);
        return in_array($roleLower, $superAliases)
            || $this->hasRole('super_admin')
            || $this->hasRole('superadmin')
            || $this->hasRole('super-admin')
            || $this->hasRole('super admin');
    }

    public function hasPermission(string $slug): bool
    {
        // Admins and superadmins have all permissions
        $adminAliases = ['admin', 'administrator'];
        $superAliases = ['super_admin', 'superadmin', 'super-admin', 'super admin'];
        
        // Case-insensitive role check
        $roleLower = strtolower($this->role);
        
        if (
            in_array($roleLower, $adminAliases) || $this->hasRole('admin') ||
            in_array($roleLower, $superAliases) ||
            $this->hasRole('super_admin') || $this->hasRole('superadmin') || $this->hasRole('super-admin') || $this->hasRole('super admin')
        ) {
            return true;
        }

        return Permission::query()
            ->where('slug', $slug)
            ->where('tenant_id', $this->tenant_id)
            ->whereHas('roles', function ($q) {
                $q->whereIn('roles.id', $this->roles()->pluck('roles.id'));
            })
            ->exists();
    }

    public function isManager()
    {
        $roleLower = strtolower($this->role);
        return in_array($roleLower, ['admin', 'manager']) || $this->hasRole('manager');
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_user');
    }

    // Admin role relationship
    public function adminRole()
    {
        return $this->belongsTo(AdminRole::class, 'admin_role_id');
    }

    // Check if user has admin permission
    public function hasAdminPermission(string $permissionSlug): bool
    {
        // Superadmin has all permissions
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Check if user has admin role with the permission
        if ($this->adminRole) {
            return $this->adminRole->hasPermission($permissionSlug);
        }

        return false;
    }

    // Check if user has any of the admin permissions
    public function hasAnyAdminPermission(array $permissionSlugs): bool
    {
        // Superadmin has all permissions
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Check if user has admin role with any of the permissions
        if ($this->adminRole) {
            return $this->adminRole->hasAnyPermission($permissionSlugs);
        }

        return false;
    }

    // RBAC additions

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role')
            ->withTimestamps()
            ->wherePivot('tenant_id', session('tenant_id'));
    }

    public function hasRole(string $slug): bool
    {
        // Check legacy role column first
        if ($this->role === $slug) return true;
        
        // Treat 'administrator' as 'admin'
        $adminAliases = ['admin', 'administrator'];
        if (in_array($slug, $adminAliases) && in_array($this->role, $adminAliases)) {
            return true;
        }
        
        // Check super admin aliases in role column
        $superAliases = ['super_admin', 'superadmin', 'super-admin', 'super admin'];
        if (in_array($slug, $superAliases) && in_array($this->role, $superAliases)) {
            return true;
        }
        
        // Check roles relationship (without tenant filter for flexibility)
        return $this->belongsToMany(Role::class, 'user_role')
            ->where('slug', $slug)
            ->exists();
    }
}