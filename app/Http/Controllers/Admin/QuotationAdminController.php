<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuotationRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class QuotationAdminController extends Controller
{
    public function index(): View
    {
        $quotations = QuotationRequest::with(['configuration.product'])->latest()->paginate(20);

        return view('admin.quotations.index', compact('quotations'));
    }

    public function updateStatus(Request $request, QuotationRequest $quotation)
    {
        $request->validate(['status' => 'required|in:pending,reviewed,quoted,closed']);
        $quotation->update(['status' => $request->status]);

        return back()->with('success', 'Quotation status updated.');
    }
}
