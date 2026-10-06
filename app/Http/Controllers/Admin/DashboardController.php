<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Component;
use App\Models\ConfigurationRule;
use App\Models\ConfigurationSession;
use App\Models\Product;
use App\Models\QuotationRequest;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_products' => Product::count(),
            'active_products' => Product::where('status', 'published')->count(),
            'draft_products' => Product::where('status', 'draft')->count(),
            'total_components' => Component::count(),
            'total_rules' => ConfigurationRule::count(),
            'active_rules' => ConfigurationRule::where('is_active', true)->count(),
            'saved_configurations' => ConfigurationSession::count(),
            'quotation_requests' => QuotationRequest::count(),
        ];

        $recentConfigurations = ConfigurationSession::with(['product', 'user'])
            ->latest()
            ->limit(5)
            ->get();

        $recentQuotations = QuotationRequest::with(['configuration.product'])
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentConfigurations', 'recentQuotations'));
    }
}
