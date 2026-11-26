<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Download;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    public function show(Download $download): StreamedResponse
    {
        abort_if($download->path === null, 404, 'This export has expired.');
        abort_unless(Storage::disk('public')->exists($download->path), 404, 'Export not found.');

        $filename = basename($download->path);

        return Storage::disk('public')->download($download->path, $filename, [
            'Content-Type' => $this->mimeFor($download->format),
        ]);
    }

    private function mimeFor(string $format): string
    {
        return match ($format) {
            'svg' => 'image/svg+xml',
            'pdf' => 'application/pdf',
            'zip' => 'application/zip',
            default => 'image/png',
        };
    }
}
