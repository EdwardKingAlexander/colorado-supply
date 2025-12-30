<?php

namespace App\Services;

use App\Models\SamOpportunityDocument;
use Illuminate\Support\Facades\Storage;

class SamDocumentParser
{
    public function parse(SamOpportunityDocument $document): array
    {
        $mime = $document->mime_type;
        $disk = $document->disk;
        $path = $document->storage_path;

        try {
            $contents = Storage::disk($disk)->get($path);
        } catch (\Throwable $e) {
            return [
                'status' => 'failed',
                'raw_text' => null,
                'error_message' => 'Unable to read file: '.$e->getMessage(),
            ];
        }

        if ($mime === 'text/plain') {
            return [
                'status' => 'success',
                'raw_text' => $contents,
                'error_message' => null,
            ];
        }

        return [
            'status' => 'failed',
            'raw_text' => "Parsing not implemented for MIME: {$mime}",
            'error_message' => "Parsing not implemented for MIME: {$mime}",
        ];
    }
}
