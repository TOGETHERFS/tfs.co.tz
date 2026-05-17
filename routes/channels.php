<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Tenant-specific channels for multi-tenant notifications
Broadcast::channel('tenant.{tenantId}', function ($user, $tenantId) {
    return $user->tenant_id === (int) $tenantId;
});

// Loan-specific channels for real-time updates
Broadcast::channel('loan.{loanId}', function ($user, $loanId) {
    $loan = \App\Models\Loan::find($loanId);
    return $loan && ($user->can('view', $loan) || $loan->client_id === $user->id);
});

// Client-specific channels
Broadcast::channel('client.{clientId}', function ($user, $clientId) {
    $client = \App\Models\Client::find($clientId);
    return $client && ($user->can('view', $client) || $user->id === (int) $clientId);
});

// Repayment notifications
Broadcast::channel('repayments.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// System-wide notifications for admins
Broadcast::channel('admin-notifications', function ($user) {
    return $user->hasRole('admin') || $user->hasRole('super-admin');
});

// Branch-specific notifications
Broadcast::channel('branch.{branchId}', function ($user, $branchId) {
    return $user->branch_id === (int) $branchId || $user->hasRole('admin');
});

// Billing notifications for tenant admins
Broadcast::channel('billing.{tenantId}', function ($user, $tenantId) {
    return $user->tenant_id === (int) $tenantId && 
           ($user->hasRole('tenant-admin') || $user->can('manage-billing'));
});