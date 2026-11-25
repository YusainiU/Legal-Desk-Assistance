<?php

namespace App\Http\Controllers;

use App\Models\CaseDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CaseDocumentController
{
    private const HEADERS = [
        'Cache-Control' => 'private, no-store',
        'X-Content-Type-Options' => 'nosniff',
        'Content-Security-Policy' => "default-src 'none'; frame-ancestors 'none'",
    ];

    public function preview(Request $request, CaseDocument $document): BinaryFileResponse
    {
        $path = $this->authorizedPath($request, $document);
        abort_unless($document->canPreview(), 415, 'Download this file to open it in a compatible application.');

        $response = response()->file($path, self::HEADERS + ['Content-Type' => $document->mime_type])
            ->setContentDisposition('inline', $document->name, str_replace('%', '', Str::ascii($document->name)));
        $response->setPrivate();

        return $response;
    }

    public function download(Request $request, CaseDocument $document): BinaryFileResponse
    {
        $response = response()->download(
            $this->authorizedPath($request, $document),
            $document->name,
            self::HEADERS + ['Content-Type' => 'application/octet-stream'],
        );
        $response->setPrivate();

        return $response;
    }

    private function authorizedPath(Request $request, CaseDocument $document): string
    {
        abort_unless($document->matter()->exists(), 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($document->path), 404);

        return $disk->path($document->path);
    }
}
