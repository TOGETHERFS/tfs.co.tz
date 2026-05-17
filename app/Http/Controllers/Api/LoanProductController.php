<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoanProduct;
use Illuminate\Http\Request;

class LoanProductController extends Controller
{
    /**
     * List active loan products for tenant.
     */
    public function index(Request $request)
    {
        $products = LoanProduct::active()->get([
            'id',
            'name',
            'description',
            'min_amount',
            'max_amount',
            'interest_rate',
            'interest_type',
            'min_term',
            'max_term',
            'processing_fee',
            'processing_fee_type',
        ]);

        // Return plain array to match frontend expectations
        return response()->json($products);
    }
}