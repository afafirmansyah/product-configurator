<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateBomExport;
use App\Models\ConfigurationSession;
use App\Services\Bom\BomDocumentRenderer;
use App\Services\Bom\BomGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BomExportController extends Controller
{
    public function __construct(
        private BomGeneratorService $bomService,
        private BomDocumentRenderer $renderer,
    ) {}

    public function pdf(ConfigurationSession $configuration): Response|RedirectResponse
    {
        $this->authorize('view', $configuration);
        $configuration->load(['product', 'user', 'bomItems.component.category']);

        $bomItems = $configuration->bom_snapshot ?? [];
        if (empty($bomItems)) {
            return back()->with('error', 'BOM has not been generated yet. Please generate the BOM first.');
        }

        $validation = $configuration->validation_errors ?? ['valid' => true, 'errors' => [], 'warnings' => []];
        $showPricing = $this->userCanSeePricing();

        $content = $this->renderer->renderPdf($configuration, $bomItems, $validation, $showPricing);
        $filename = $this->bomService->getDocumentName($configuration, 'BOM', 'pdf');

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function excel(ConfigurationSession $configuration): StreamedResponse|RedirectResponse
    {
        $this->authorize('view', $configuration);
        $configuration->load(['product', 'user', 'bomItems.component.category']);

        $bomItems = $configuration->bom_snapshot ?? [];
        if (empty($bomItems)) {
            return back()->with('error', 'BOM has not been generated yet. Please generate the BOM first.');
        }

        $showPricing = $this->userCanSeePricing();
        $content = $this->renderer->renderExcel($configuration, $bomItems, $showPricing);
        $filename = $this->bomService->getDocumentName($configuration, 'BOM', 'xlsx');

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Queue an asynchronous export. The heavy document build runs on the queue;
     * the user is notified with a signed download link when the file is ready.
     */
    public function queue(ConfigurationSession $configuration, string $format): RedirectResponse
    {
        $this->authorize('view', $configuration);

        abort_unless(in_array($format, ['pdf', 'xlsx'], true), 404);

        if (empty($configuration->bom_snapshot ?? [])) {
            return back()->with('error', 'BOM has not been generated yet. Please generate the BOM first.');
        }

        GenerateBomExport::dispatch(
            $configuration->id,
            $format,
            auth()->id(),
            $this->userCanSeePricing(),
        );

        return back()->with('success', 'Your export is being prepared. You will be notified with a download link when it is ready.');
    }

    private function userCanSeePricing(): bool
    {
        return auth()->user()->hasRole(['super_admin', 'product_manager', 'sales', 'engineering']);
    }
}
