<?php

namespace App\Jobs;

use App\Models\ConfigurationSession;
use App\Models\User;
use App\Notifications\BomExportReady;
use App\Services\Bom\BomDocumentRenderer;
use App\Services\Bom\BomGeneratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GenerateBomExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Disk and directory where generated exports are stored.
     */
    public const DISK = 'local';

    public const DIRECTORY = 'exports';

    public function __construct(
        public readonly int $configurationId,
        public readonly string $format,
        public readonly int $userId,
        public readonly bool $showPricing,
    ) {}

    public function handle(BomDocumentRenderer $renderer, BomGeneratorService $bomService): void
    {
        $configuration = ConfigurationSession::with(['product', 'user', 'bomItems.component.category'])
            ->find($this->configurationId);
        $user = User::find($this->userId);

        if ($configuration === null || $user === null) {
            return;
        }

        $bomItems = $configuration->bom_snapshot ?? [];

        if (empty($bomItems)) {
            return;
        }

        $content = $this->format === 'pdf'
            ? $renderer->renderPdf(
                $configuration,
                $bomItems,
                $configuration->validation_errors ?? ['valid' => true, 'errors' => [], 'warnings' => []],
                $this->showPricing,
            )
            : $renderer->renderExcel($configuration, $bomItems, $this->showPricing);

        $filename = $bomService->getDocumentName($configuration, 'BOM', $this->format);
        $path = self::DIRECTORY.'/'.Str::ulid().'.'.$this->format;

        Storage::disk(self::DISK)->put($path, $content);

        $user->notify(new BomExportReady($path, $filename, $configuration->configuration_number));
    }
}
