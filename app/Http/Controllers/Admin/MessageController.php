<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /**
     * Show admin control page for messaging per tenant.
     */
    public function control(Request $request)
    {
        $query = Tenant::query()->orderBy('name');

        if ($search = $request->get('q')) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
        }

        $tenants = $query->paginate(15);

        return view('admin.messages.control', compact('tenants'));
    }

    /**
     * Toggle messaging_enabled for a tenant.
     */
    public function toggle(Tenant $tenant)
    {
        $tenant->update([
            'messaging_enabled' => !$tenant->messaging_enabled,
        ]);

        return back()->with('status', 'Messaging ' . ($tenant->messaging_enabled ? 'enabled' : 'disabled') . ' for tenant: ' . $tenant->name);
    }
}