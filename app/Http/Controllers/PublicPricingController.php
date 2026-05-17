<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;

class PublicPricingController extends Controller
{
    /**
     * Show public pricing page with active plans.
     */
    public function index(Request $request)
    {
        // Only show Starter/Growth/Enterprise for clarity
        $plans = Plan::active()
            ->whereIn('code', ['starter','growth','enterprise'])
            ->get()
            ->sortBy(function ($plan) {
                $order = ['starter' => 1, 'growth' => 2, 'enterprise' => 3];
                return $order[$plan->code] ?? 999;
            })
            ->values();

        return view('public.pricing', compact('plans'));
    }
}