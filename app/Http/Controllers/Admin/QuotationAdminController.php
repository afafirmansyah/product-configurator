<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuotationRequest;
use App\Notifications\QuotationStatusUpdated;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class QuotationAdminController extends Controller
{
    public function index(): View
    {
        $quotations = QuotationRequest::with(['configuration.product'])->latest()->paginate(20);

        return view('admin.quotations.index', compact('quotations'));
    }

    public function updateStatus(Request $request, QuotationRequest $quotation): RedirectResponse
    {
        $request->validate(['status' => 'required|in:pending,reviewed,quoted,closed']);

        $previousStatus = $quotation->status;
        $quotation->update(['status' => $request->status]);

        // Notify the submitter of the lifecycle change (skip no-op updates).
        if ($previousStatus !== $quotation->status) {
            $quotation->loadMissing('user');
            $quotation->user?->notify(new QuotationStatusUpdated($quotation, $previousStatus));
        }

        return back()->with('success', 'Quotation status updated.');
    }
}
