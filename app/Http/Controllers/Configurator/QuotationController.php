<?php

namespace App\Http\Controllers\Configurator;

use App\Http\Controllers\Controller;
use App\Models\ConfigurationSession;
use App\Models\QuotationRequest;
use App\Notifications\QuotationSubmitted;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QuotationController extends Controller
{
    public function store(Request $request, string $ulid): RedirectResponse
    {
        $session = ConfigurationSession::with('user')->where('ulid', $ulid)->firstOrFail();

        $this->authorize('createQuotation', $session);

        $data = $request->validate([
            'company' => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:50',
            'country' => 'nullable|string|max:100',
            'project_name' => 'nullable|string|max:255',
            'project_location' => 'nullable|string|max:255',
            'quantity' => 'required|integer|min:1',
            'required_delivery_date' => 'nullable|date',
            'remarks' => 'nullable|string',
        ]);

        $year = now()->year;
        $count = QuotationRequest::whereYear('created_at', $year)->count() + 1;

        $quotation = QuotationRequest::create(array_merge($data, [
            'ulid' => Str::ulid(),
            'reference_number' => sprintf('QR-%d-%05d', $year, $count),
            'configuration_session_id' => $session->id,
            'user_id' => auth()->id(),
            'status' => 'pending',
        ]));

        // Notify the configuration owner that their quotation request was received.
        $session->user?->notify(new QuotationSubmitted($quotation));

        return redirect()->route('configurator.show', $ulid)
            ->with('success', 'Quotation request submitted successfully. Our team will contact you shortly.');
    }
}
