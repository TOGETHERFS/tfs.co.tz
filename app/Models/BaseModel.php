<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

abstract class BaseModel extends Model
{
    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = null;

            // Always prioritize authenticated user's tenant_id (source of truth)
            if (auth()->check()) {
                $tenantId = auth()->user()->tenant_id;
            } else {
                // Fallback to session only if not authenticated
                $request = app('request');
                $hasSession = method_exists($request, 'hasSession') && $request->hasSession();
                if ($hasSession && session()->has('tenant_id')) {
                    $tenantId = session('tenant_id');
                }
            }

            if ($tenantId) {
                $builder->where($builder->getModel()->getTable() . '.tenant_id', $tenantId);
            } else {
                // Log when no tenant context is found to help debug empty list issues
                \Log::warning('BaseModel: No tenant_id found for query', [
                    'model' => get_class($builder->getModel()),
                    'auth_check' => auth()->check(),
                    'user_id' => auth()->id(),
                    'has_session' => method_exists(app('request'), 'hasSession') && app('request')->hasSession(),
                    'session_tenant' => session('tenant_id'),
                    'url' => request()->fullUrl()
                ]);
            }
        });

        static::creating(function ($model) {
            $request = app('request');
            if (!$model->tenant_id) {
                $tenantId = null;

                // Always prioritize authenticated user's tenant_id (source of truth)
                if (auth()->check()) {
                    $tenantId = auth()->user()->tenant_id;
                } else {
                    // Fallback to session only if not authenticated
                    $hasSession = method_exists($request, 'hasSession') && $request->hasSession();
                    if ($hasSession && session()->has('tenant_id')) {
                        $tenantId = session('tenant_id');
                    }
                }

                if ($tenantId) {
                    $model->tenant_id = $tenantId;
                }
            }
        });
    }

    /**
     * Get the tenant that owns the model.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}