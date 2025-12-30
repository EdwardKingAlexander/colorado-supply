<?php

namespace App\Services;

use App\Models\SamOpportunity;
use App\Models\SamOpportunityDocumentChunk;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SamRagRetriever
{
    public function retrieve(SamOpportunity $opportunity, string $query, int $limit = 5): array
    {
        $chunks = SamOpportunityDocumentChunk::whereIn(
            'sam_opportunity_document_id',
            $opportunity->documents()->pluck('id')
        )->get();

        if ($chunks->isEmpty()) {
            return [
                'query' => $query,
                'opportunity_id' => $opportunity->id,
                'top_chunks' => [],
                'answer' => 'No indexed content available for this opportunity yet.',
            ];
        }

        $scores = $chunks->map(function ($chunk) use ($query) {
            $score = $this->score($query, $chunk->text);
            return [
                'chunk_id' => $chunk->id,
                'document_id' => $chunk->sam_opportunity_document_id,
                'score' => $score,
                'text' => $chunk->text,
            ];
        })->sortByDesc('score')->values()->take($limit)->all();

        $answer = $this->buildStubAnswer($query, $scores);

        return [
            'query' => $query,
            'opportunity_id' => $opportunity->id,
            'top_chunks' => $scores,
            'answer' => $answer,
        ];
    }

    protected function score(string $query, string $text): float
    {
        $queryWords = $this->tokens($query);
        $textWords = $this->tokens($text);

        if (empty($textWords)) {
            return 0.0;
        }

        $overlap = count(array_intersect($queryWords, $textWords));
        $hashTieBreaker = hexdec(substr(md5($text), 0, 4)) / 0xffff;

        return $overlap + $hashTieBreaker * 0.01;
    }

    protected function tokens(string $input): array
    {
        $clean = Str::lower(preg_replace('/[^a-z0-9\s]/i', ' ', $input));
        $parts = array_filter(explode(' ', $clean), fn ($t) => strlen($t) > 0);
        return array_values($parts);
    }

    protected function buildStubAnswer(string $query, array $topChunks): string
    {
        if (empty($topChunks)) {
            return 'No indexed content available for this opportunity yet.';
        }

        $snippets = array_map(function ($chunk) {
            return '- '.$this->truncate($chunk['text']);
        }, $topChunks);

        return "Stubbed answer for: {$query}\nContext:\n".implode("\n", $snippets);
    }

    protected function truncate(string $text, int $limit = 200): string
    {
        return Str::limit(trim(preg_replace('/\s+/', ' ', $text)), $limit, '...');
    }
}
