<?php

namespace App\Filament\Widgets;

use App\Models\SamOpportunity;
use App\Models\SamOpportunityDocument;
use App\Models\SamOpportunityDocumentEmbedding;
use App\Models\SamOpportunityDocumentParse;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class SamInsightsStats extends BaseWidget
{
    protected function getStats(): array
    {
        $totalOpps = SamOpportunity::count();
        $favorited = SamOpportunity::whereHas('favoritedBy')->count();

        $docCounts = SamOpportunityDocument::selectRaw('count(*) as total_docs')->first()?->total_docs ?? 0;
        $parseSuccess = SamOpportunityDocumentParse::where('status', 'success')->count();
        $parsedPct = $docCounts > 0 ? round(($parseSuccess / $docCounts) * 100) : 0;

        $chunkCounts = DB::table('sam_opportunity_document_chunks')->count();
        $embeddingCounts = SamOpportunityDocumentEmbedding::count();
        $embedPct = $chunkCounts > 0 ? round(($embeddingCounts / $chunkCounts) * 100) : 0;

        return [
            Stat::make('SAM Opportunities', $totalOpps),
            Stat::make('Favorited Opportunities', $favorited),
            Stat::make('Parsed Docs', "{$parseSuccess} / {$docCounts} ({$parsedPct}%)"),
            Stat::make('Embedded Chunks', "{$embeddingCounts} / {$chunkCounts} ({$embedPct}%)"),
        ];
    }
}
