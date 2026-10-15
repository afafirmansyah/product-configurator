<?php

namespace App\Http\Controllers\Configurator;

use App\Http\Controllers\Controller;
use App\Models\QuotationRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class QuoteExportController extends Controller
{
    /**
     * Stream a formatted quotation PDF for the given quotation request.
     */
    public function pdf(QuotationRequest $quotation): Response
    {
        $this->authorize('view', $quotation);

        $quotation->load(['configuration.product', 'configuration.user', 'user']);
        $configuration = $quotation->configuration;

        $bomItems = $configuration?->bom_snapshot ?? [];
        $showPricing = auth()->user()->hasRole(['super_admin', 'product_manager', 'sales', 'engineering']);

        $pdf = Pdf::loadView('sales.exports.quote-pdf', compact('quotation', 'configuration', 'bomItems', 'showPricing'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('Quote-'.$quotation->reference_number.'.pdf');
    }
}
