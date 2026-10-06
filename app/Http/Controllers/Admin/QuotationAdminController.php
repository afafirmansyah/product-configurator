<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuotationRequest;
use Illuminate\Http\Request;

class QuotationAdminController extends Controller
{
    public function updateStatus(Request $request, QuotationRequest $quotation)
    {
        $request->validate(['status' => 'required|in:pending,reviewed,quoted,closed']);
        $quotation->update(['status' => $request->status]);
        return back()->with('success', 'Quotation status updated.');
    }
}
