<?php

namespace App\Services;

use App\Models\SamOpportunityDocumentParse;

class SamDocumentChunker
{
    protected int $maxCharacters;

    public function __construct(int $maxCharacters = 1000)
    {
        $this->maxCharacters = $maxCharacters;
    }

    public function chunk(SamOpportunityDocumentParse $parse): array
    {
        $text = $parse->raw_text ?? '';
        if (trim($text) === '') {
            return [];
        }

        $chunks = str_split($text, $this->maxCharacters);

        return array_values(array_map(function ($chunkText, $index) {
            return [
                'chunk_index' => $index,
                'text' => $chunkText,
                'token_count' => $this->estimateTokens($chunkText),
            ];
        }, $chunks, array_keys($chunks)));
    }

    protected function estimateTokens(string $text): int
    {
        return max(1, (int) ceil(strlen($text) / 4));
    }
}
