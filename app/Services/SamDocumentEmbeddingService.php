<?php

namespace App\Services;

class SamDocumentEmbeddingService
{
    public function embed(string $text): array
    {
        // Deterministic stub embedding: fixed-length floats based on hash.
        $hash = md5($text);
        $vector = [];
        for ($i = 0; $i < 8; $i++) {
            $chunk = substr($hash, $i * 4, 4);
            $vector[] = round(hexdec($chunk) / 0xffff, 6);
        }

        return [
            'embedding_model' => 'stub',
            'vector' => $vector,
        ];
    }
}
