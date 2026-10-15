<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateBomExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StoredExportController extends Controller
{
    /**
     * Stream a previously generated export. Access is granted by a signed,
     * time-limited URL (validated by the 'signed' middleware on the route), so
     * only the recipient of the notification can download the file. The path is
     * constrained to the exports directory to prevent traversal.
     */
    public function download(Request $request): StreamedResponse
    {
        $path = (string) $request->query('path');

        abort_unless(
            Str::startsWith($path, GenerateBomExport::DIRECTORY.'/') && ! Str::contains($path, '..'),
            404,
        );

        $disk = Storage::disk(GenerateBomExport::DISK);

        abort_unless($disk->exists($path), 404);

        return $disk->download($path, basename($path));
    }
}
