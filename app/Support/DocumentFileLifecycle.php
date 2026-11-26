<?php

namespace App\Support;

use App\Models\CaseDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class DocumentFileLifecycle
{
    public function deferDeletion(string $path): void
    {
        DB::table('document_file_deletions')->insertOrIgnore(['path' => $path, 'created_at' => now()]);
        DB::afterCommit(fn () => $this->remove($path));
    }

    public function remove(string $path): bool
    {
        try {
            if (! preg_match('~\Acase-documents/[0-9]+/[A-Za-z0-9._-]+\z~D', $path)
                || in_array(basename($path), ['.', '..'], true)
                || CaseDocument::query()->where('path', $path)->exists()) {
                return false;
            }
            $disk = Storage::disk('local');
            if ($disk->exists($path) && ! $disk->delete($path)) {
                return false;
            }
            DB::table('document_file_deletions')->where('path', $path)->delete();
            return true;
        } catch (\Throwable $error) {
            Log::warning('Case document cleanup failed', ['exception_type' => get_class($error)]);
            return false;
        }
    }
}
