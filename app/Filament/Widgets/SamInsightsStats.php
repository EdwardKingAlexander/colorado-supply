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
            Stat::make('SAM Opportunities', $totalOpps)
                ->description('Tracked federal opportunities')
                ->color('primary'),
            Stat::make('Favorited Opportunities', $favorited)
                ->description('Saved by users for monitoring')
                ->color('success'),
            Stat::make('Parsed Docs', "{$parseSuccess} / {$docCounts} ({$parsedPct}%)")
                ->description('Successful document parse coverage')
                ->color($parsedPct >= 90 ? 'success' : ($parsedPct >= 50 ? 'warning' : 'danger')),
            Stat::make('Embedded Chunks', "{$embeddingCounts} / {$chunkCounts} ({$embedPct}%)")
                ->description('Vectorized chunk readiness')
                ->color($embedPct >= 90 ? 'success' : ($embedPct >= 50 ? 'warning' : 'danger')),
        ];
    }
}
