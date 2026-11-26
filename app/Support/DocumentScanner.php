<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

final class DocumentScanner
{
    public function check(string $path): void
    {
        if (! config('document-security.scan_enabled')) {
            return;
        }

        try {
            $process = new Process([
                config('document-security.scanner'), '--no-summary', '--', $path,
            ]);
            $process->setTimeout(max(1, min(300, (int) config('document-security.timeout'))));
            $process->disableOutput();
            $status = $process->run();
        } catch (\Throwable $error) {
            $status = -1;
        }

        if ($status !== 0) {
            Log::warning('Case document upload rejected by scanner', [
                'actor_id' => auth()->id(),
                'result' => $status === 1 ? 'detected' : 'scanner_unavailable',
            ]);
            throw ValidationException::withMessages([
                'document' => $status === 1
                    ? 'The document failed the malware scan and was rejected.'
                    : 'Document scanning is unavailable. Please try again later.',
            ]);
        }
    }
}
