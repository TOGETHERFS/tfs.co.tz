<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\Payment;
use App\Models\Invoice;

class FindTenant extends Command
{
    protected $signature = 'tenant:find {search}';
    protected $description = 'Find tenant by partial email or name';

    public function handle()
    {
        $search = $this->argument('search');

        $tenants = Tenant::where('contact_email', 'like', "%{$search}%")
            ->orWhere('name', 'like', "%{$search}%")
            ->get();

        if ($tenants->isEmpty()) {
            $this->error("No tenants found matching: {$search}");
            return 1;
        }

        foreach ($tenants as $tenant) {
            $revenue = Payment::where('tenant_id', $tenant->id)->whereIn('status', ['success', 'completed'])->sum('amount');
            $pendingInvoices = Invoice::where('tenant_id', $tenant->id)->where('status', 'pending')->count();
            $this->info("ID: {$tenant->id} | Name: {$tenant->name}");
            $this->info("  Email: {$tenant->contact_email}");
            $this->info("  Status: {$tenant->status} | Plan: {$tenant->plan_slug}");
            $this->info("  Revenue: TZS " . number_format($revenue) . " | Pending invoices: {$pendingInvoices}");
            $this->info("---");
        }

        return 0;
    }
}
