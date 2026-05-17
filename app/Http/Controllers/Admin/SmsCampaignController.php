<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsCampaign;
use App\Models\SmsLog;
use App\Models\Tenant;
use App\Models\SenderId;
use App\Services\SmsManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SmsCampaignController extends Controller
{
    protected SmsManager $smsManager;

    public function __construct(SmsManager $smsManager)
    {
        $this->smsManager = $smsManager;
    }

    /**
     * Display a listing of SMS campaigns.
     */
    public function index(Request $request)
    {
        $query = SmsCampaign::with(['tenant', 'user', 'senderId']);

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Tenant filter
        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%")
                  ->orWhereHas('tenant', function ($tq) use ($search) {
                      $tq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $campaigns = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Get summary statistics
        $stats = [
            'total_campaigns' => SmsCampaign::count(),
            'active_campaigns' => SmsCampaign::whereIn('status', ['scheduled', 'running', 'paused'])->count(),
            'completed_campaigns' => SmsCampaign::completed()->count(),
            'failed_campaigns' => SmsCampaign::failed()->count(),
            'total_recipients' => SmsCampaign::sum('total_recipients'),
            'total_sent' => SmsCampaign::sum('sent_count'),
            'total_delivered' => SmsCampaign::sum('delivered_count'),
            'total_cost' => SmsCampaign::sum('actual_cost')
        ];

        $tenants = Tenant::orderBy('name')->get();

        return view('admin.sms-campaigns.index', compact('campaigns', 'stats', 'tenants'));
    }

    /**
     * Display the specified SMS campaign.
     */
    public function show(SmsCampaign $smsCampaign)
    {
        $smsCampaign->load(['tenant', 'user', 'senderId', 'smsLogs']);
        
        // Get detailed statistics
        $stats = [
            'progress_percentage' => $smsCampaign->progress,
            'delivery_rate' => $smsCampaign->delivery_rate,
            'cost_per_sms' => $smsCampaign->cost_per_sms,
            'estimated_completion' => $this->estimateCompletion($smsCampaign),
            'hourly_stats' => $this->getHourlyStats($smsCampaign),
            'status_breakdown' => $this->getStatusBreakdown($smsCampaign)
        ];

        // Get recent SMS logs for this campaign
        $recentLogs = $smsCampaign->smsLogs()
            ->with('tenant')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return view('admin.sms-campaigns.show', compact('smsCampaign', 'stats', 'recentLogs'));
    }

    /**
     * Pause a running campaign.
     */
    public function pause(SmsCampaign $smsCampaign)
    {
        if (!$smsCampaign->canBePaused()) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign cannot be paused in its current state.'
            ]);
        }

        $success = $smsCampaign->pause();

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Campaign paused successfully.',
                'status' => $smsCampaign->fresh()->status
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to pause campaign.'
        ]);
    }

    /**
     * Resume a paused campaign.
     */
    public function resume(SmsCampaign $smsCampaign)
    {
        if (!$smsCampaign->canBeResumed()) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign cannot be resumed in its current state.'
            ]);
        }

        $success = $smsCampaign->resume();

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Campaign resumed successfully.',
                'status' => $smsCampaign->fresh()->status
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to resume campaign.'
        ]);
    }

    /**
     * Cancel a campaign.
     */
    public function cancel(SmsCampaign $smsCampaign)
    {
        if (!$smsCampaign->canBeCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign cannot be cancelled in its current state.'
            ]);
        }

        $success = $smsCampaign->cancel();

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Campaign cancelled successfully.',
                'status' => $smsCampaign->fresh()->status
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to cancel campaign.'
        ]);
    }

    /**
     * Retry failed messages in a campaign.
     */
    public function retryFailed(SmsCampaign $smsCampaign)
    {
        $failedLogs = $smsCampaign->smsLogs()
            ->where('status', 'failed')
            ->where('retry_count', '<', 3)
            ->get();

        if ($failedLogs->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No failed messages available for retry.'
            ]);
        }

        $retryCount = 0;
        foreach ($failedLogs as $log) {
            try {
                // Reset status and increment retry count
                $log->update([
                    'status' => 'pending',
                    'retry_count' => $log->retry_count + 1,
                    'error' => null
                ]);
                $retryCount++;
            } catch (\Exception $e) {
                // Log error but continue with other messages
                \Log::error("Failed to retry SMS log {$log->id}: " . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => "{$retryCount} messages queued for retry.",
            'retry_count' => $retryCount
        ]);
    }

    /**
     * Get campaign analytics data.
     */
    public function analytics(Request $request)
    {
        $validated = $request->validate([
            'period' => 'nullable|in:7d,30d,90d,1y',
            'tenant_id' => 'nullable|exists:tenants,id'
        ]);

        $period = $validated['period'] ?? '30d';
        $tenantId = $validated['tenant_id'] ?? null;

        $days = match($period) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            '1y' => 365,
            default => 30
        };

        $query = SmsCampaign::where('created_at', '>=', now()->subDays($days));
        
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $campaigns = $query->get();

        $analytics = [
            'total_campaigns' => $campaigns->count(),
            'total_recipients' => $campaigns->sum('total_recipients'),
            'total_sent' => $campaigns->sum('sent_count'),
            'total_delivered' => $campaigns->sum('delivered_count'),
            'total_failed' => $campaigns->sum('failed_count'),
            'total_cost' => $campaigns->sum('actual_cost'),
            'average_delivery_rate' => $campaigns->avg('delivery_rate'),
            'daily_stats' => $this->getDailyStats($campaigns, $days),
            'status_distribution' => $this->getStatusDistribution($campaigns),
            'top_tenants' => $this->getTopTenants($campaigns),
            'cost_analysis' => $this->getCostAnalysis($campaigns)
        ];

        return response()->json($analytics);
    }

    /**
     * Export campaign data.
     */
    public function export(Request $request)
    {
        $validated = $request->validate([
            'format' => 'required|in:csv,xlsx',
            'campaign_ids' => 'nullable|array',
            'campaign_ids.*' => 'exists:sms_campaigns,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'status' => 'nullable|in:draft,scheduled,running,paused,completed,failed,cancelled'
        ]);

        $query = SmsCampaign::with(['tenant', 'user', 'senderId']);

        if (!empty($validated['campaign_ids'])) {
            $query->whereIn('id', $validated['campaign_ids']);
        }

        if (!empty($validated['date_from'])) {
            $query->whereDate('created_at', '>=', $validated['date_from']);
        }

        if (!empty($validated['date_to'])) {
            $query->whereDate('created_at', '<=', $validated['date_to']);
        }

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $campaigns = $query->orderBy('created_at', 'desc')->get();

        $filename = 'sms_campaigns_' . now()->format('Y-m-d_H-i-s') . '.' . $validated['format'];

        if ($validated['format'] === 'csv') {
            return $this->exportToCsv($campaigns, $filename);
        } else {
            return $this->exportToExcel($campaigns, $filename);
        }
    }

    /**
     * Estimate campaign completion time.
     */
    protected function estimateCompletion(SmsCampaign $smsCampaign): ?string
    {
        if (!in_array($smsCampaign->status, ['running', 'paused'])) {
            return null;
        }

        if ($smsCampaign->sent_count === 0) {
            return null;
        }

        $elapsed = $smsCampaign->started_at->diffInMinutes(now());
        $rate = $smsCampaign->sent_count / max($elapsed, 1); // SMS per minute
        $remaining = $smsCampaign->total_recipients - $smsCampaign->sent_count;
        
        if ($rate > 0) {
            $estimatedMinutes = $remaining / $rate;
            return now()->addMinutes($estimatedMinutes)->format('Y-m-d H:i:s');
        }

        return null;
    }

    /**
     * Get hourly statistics for a campaign.
     */
    protected function getHourlyStats(SmsCampaign $smsCampaign): array
    {
        if (!$smsCampaign->started_at) {
            return [];
        }

        $stats = [];
        $startHour = $smsCampaign->started_at->startOfHour();
        $endHour = ($smsCampaign->completed_at ?? now())->startOfHour();

        for ($hour = $startHour->copy(); $hour <= $endHour; $hour->addHour()) {
            $nextHour = $hour->copy()->addHour();
            
            $sent = $smsCampaign->smsLogs()
                ->where('created_at', '>=', $hour)
                ->where('created_at', '<', $nextHour)
                ->count();

            $delivered = $smsCampaign->smsLogs()
                ->where('status', 'delivered')
                ->where('delivered_at', '>=', $hour)
                ->where('delivered_at', '<', $nextHour)
                ->count();

            $stats[] = [
                'hour' => $hour->format('H:i'),
                'sent' => $sent,
                'delivered' => $delivered
            ];
        }

        return $stats;
    }

    /**
     * Get status breakdown for a campaign.
     */
    protected function getStatusBreakdown(SmsCampaign $smsCampaign): array
    {
        return [
            'pending' => $smsCampaign->smsLogs()->where('status', 'pending')->count(),
            'sent' => $smsCampaign->smsLogs()->where('status', 'sent')->count(),
            'delivered' => $smsCampaign->smsLogs()->where('status', 'delivered')->count(),
            'failed' => $smsCampaign->smsLogs()->where('status', 'failed')->count()
        ];
    }

    /**
     * Get daily statistics for campaigns.
     */
    protected function getDailyStats($campaigns, int $days): array
    {
        $stats = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $nextDate = $date->copy()->addDay();
            
            $dayCampaigns = $campaigns->filter(function ($campaign) use ($date, $nextDate) {
                return $campaign->created_at >= $date && $campaign->created_at < $nextDate;
            });

            $stats[] = [
                'date' => $date->format('Y-m-d'),
                'campaigns' => $dayCampaigns->count(),
                'recipients' => $dayCampaigns->sum('total_recipients'),
                'sent' => $dayCampaigns->sum('sent_count'),
                'cost' => $dayCampaigns->sum('actual_cost')
            ];
        }

        return $stats;
    }

    /**
     * Get status distribution for campaigns.
     */
    protected function getStatusDistribution($campaigns): array
    {
        return [
            'draft' => $campaigns->where('status', 'draft')->count(),
            'scheduled' => $campaigns->where('status', 'scheduled')->count(),
            'running' => $campaigns->where('status', 'running')->count(),
            'paused' => $campaigns->where('status', 'paused')->count(),
            'completed' => $campaigns->where('status', 'completed')->count(),
            'failed' => $campaigns->where('status', 'failed')->count(),
            'cancelled' => $campaigns->where('status', 'cancelled')->count()
        ];
    }

    /**
     * Get top tenants by campaign activity.
     */
    protected function getTopTenants($campaigns): array
    {
        return $campaigns->groupBy('tenant_id')
            ->map(function ($tenantCampaigns) {
                $tenant = $tenantCampaigns->first()->tenant;
                return [
                    'tenant_name' => $tenant->name,
                    'campaigns' => $tenantCampaigns->count(),
                    'recipients' => $tenantCampaigns->sum('total_recipients'),
                    'cost' => $tenantCampaigns->sum('actual_cost')
                ];
            })
            ->sortByDesc('campaigns')
            ->take(10)
            ->values()
            ->toArray();
    }

    /**
     * Get cost analysis for campaigns.
     */
    protected function getCostAnalysis($campaigns): array
    {
        return [
            'total_estimated' => $campaigns->sum('estimated_cost'),
            'total_actual' => $campaigns->sum('actual_cost'),
            'average_cost_per_campaign' => $campaigns->avg('actual_cost'),
            'average_cost_per_sms' => $campaigns->sum('actual_cost') / max($campaigns->sum('sent_count'), 1)
        ];
    }

    /**
     * Export campaigns to CSV.
     */
    protected function exportToCsv($campaigns, string $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($campaigns) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'ID', 'Tenant', 'Name', 'Status', 'Recipients', 'Sent', 'Delivered', 
                'Failed', 'Cost', 'Created At', 'Started At', 'Completed At'
            ]);

            foreach ($campaigns as $campaign) {
                fputcsv($file, [
                    $campaign->id,
                    $campaign->tenant->name,
                    $campaign->name,
                    $campaign->status,
                    $campaign->total_recipients,
                    $campaign->sent_count,
                    $campaign->delivered_count,
                    $campaign->failed_count,
                    $campaign->actual_cost,
                    $campaign->created_at->format('Y-m-d H:i:s'),
                    $campaign->started_at?->format('Y-m-d H:i:s'),
                    $campaign->completed_at?->format('Y-m-d H:i:s')
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export campaigns to Excel (simplified version).
     */
    protected function exportToExcel($campaigns, string $filename)
    {
        // For now, return CSV format with Excel headers
        // In a real implementation, you would use a library like PhpSpreadsheet
        return $this->exportToCsv($campaigns, $filename);
    }
}