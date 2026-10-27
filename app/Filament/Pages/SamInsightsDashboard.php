<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\SamInsightsStats;
use App\Models\SamOpportunity;
use App\Models\SamOpportunityDocument;
use App\Models\SamOpportunityDocumentChunk;
use App\Models\SamOpportunityDocumentEmbedding;
use App\Models\SamOpportunityDocumentParse;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class SamInsightsDashboard extends Page implements HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static UnitEnum|string|null $navigationGroup = 'SAM';

    protected static ?string $navigationLabel = 'SAM Insights';

    protected string $view = 'filament.pages.sam-insights-dashboard';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->baseQuery())
            ->columns([
                // Phones: one readable column instead of nine squeezed ones.
                TextColumn::make('mobile_summary')
                    ->label('Opportunity')
                    ->state(fn (SamOpportunity $record) => $record->title)
                    ->description(fn (SamOpportunity $record) => collect([
                        $record->agency,
                        $record->response_deadline ? 'Due '.$record->response_deadline->format('M j, Y') : null,
                        self::pipelineLabel($record->pipeline_status),
                        "Docs {$record->parsed_docs}/{$record->documents_count} parsed",
                        "{$record->favorites_count} fav",
                    ])->filter()->implode(' · '))
                    ->weight('medium')
                    ->wrap()
                    ->hiddenFrom('md'),
                TextColumn::make('title')
                    ->label('Title')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->visibleFrom('md'),
                TextColumn::make('agency')
                    ->label('Agency')
                    ->default('-')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->visibleFrom('lg'),
                TextColumn::make('response_deadline')
                    ->label('Due')
                    ->date()
                    ->sortable()
                    ->visibleFrom('md'),
                TextColumn::make('favorites_count')
                    ->label('Favorites')
                    ->sortable()
                    ->visibleFrom('xl'),
                TextColumn::make('documents_count')
                    ->label('Docs')
                    ->sortable()
                    ->visibleFrom('xl'),
                TextColumn::make('parsed_docs')
                    ->label('Parsed')
                    ->formatStateUsing(fn ($state, $record) => "{$state} / {$record->documents_count}")
                    ->color(fn ($state, $record) => $state >= $record->documents_count && $record->documents_count > 0 ? 'success' : ($record->documents_count > 0 ? 'warning' : 'secondary'))
                    ->visibleFrom('lg'),
                TextColumn::make('chunks_count')
                    ->label('Chunks')
                    ->sortable()
                    ->visibleFrom('xl'),
                TextColumn::make('embeddings_count')
                    ->label('Embeddings')
                    ->sortable()
                    ->visibleFrom('xl'),
                BadgeColumn::make('pipeline_status')
                    ->label('Pipeline')
                    ->visibleFrom('md')
                    ->colors([
                        'success' => fn ($record) => $record->pipeline_status === 'complete',
                        'warning' => fn ($record) => $record->pipeline_status === 'partial',
                        'danger' => fn ($record) => $record->pipeline_status === 'needs_attention',
                        'secondary' => fn ($record) => $record->pipeline_status === 'empty',
                    ])
                    ->formatStateUsing(fn ($state) => self::pipelineLabel($state)),
            ])
            ->filters([
                Tables\Filters\Filter::make('needs_attention')
                    ->label('Needs attention')
                    ->query(fn (Builder $query) => $query->where(function ($q) {
                        $q->whereColumn('parsed_docs', '<', 'documents_count')
                            ->orWhereColumn('chunks_count', '<', 'documents_count')
                            ->orWhereColumn('embeddings_count', '<', 'chunks_count');
                    })),
            ])
            ->actions([
                Action::make('open')
                    ->label('Open in Favorites')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->iconButton()
                    ->tooltip('Open in Favorites')
                    ->url(fn (SamOpportunity $record) => SamFavorites::getUrl(['focus' => $record->id], panel: 'admin'))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('response_deadline', 'asc')
            ->recordUrl(null);
    }

    protected static function pipelineLabel(?string $state): string
    {
        return match ($state) {
            'complete' => 'Complete',
            'partial' => 'Partial',
            'needs_attention' => 'Needs Attention',
            default => 'No Data',
        };
    }

    protected function baseQuery(): Builder
    {
        $documents = SamOpportunityDocument::selectRaw('sam_opportunity_id, count(*) as documents_count')
            ->groupBy('sam_opportunity_id');

        $parsedDocs = SamOpportunityDocumentParse::selectRaw('sam_opportunity_documents.sam_opportunity_id, count(*) as parsed_docs')
            ->join('sam_opportunity_documents', 'sam_opportunity_documents.id', '=', 'sam_opportunity_document_parses.sam_opportunity_document_id')
            ->where('sam_opportunity_document_parses.status', 'success')
            ->groupBy('sam_opportunity_documents.sam_opportunity_id');

        $chunks = SamOpportunityDocumentChunk::selectRaw('sam_opportunity_document_id, count(*) as chunks_count')
            ->groupBy('sam_opportunity_document_id');

        $chunksByOpportunity = SamOpportunityDocument::selectRaw('sam_opportunity_id, coalesce(sum(chunks_count),0) as chunks_total')
            ->leftJoinSub($chunks, 'chunk_counts', 'chunk_counts.sam_opportunity_document_id', '=', 'sam_opportunity_documents.id')
            ->groupBy('sam_opportunity_id');

        $embeddings = SamOpportunityDocumentEmbedding::selectRaw('sam_opportunity_document_chunks.sam_opportunity_document_id, count(*) as embeddings_count')
            ->join('sam_opportunity_document_chunks', 'sam_opportunity_document_chunks.id', '=', 'sam_opportunity_document_embeddings.sam_opportunity_document_chunk_id')
            ->groupBy('sam_opportunity_document_chunks.sam_opportunity_document_id');

        $embeddingsByOpportunity = SamOpportunityDocument::selectRaw('sam_opportunity_id, coalesce(sum(embeddings_count),0) as embeddings_total')
            ->leftJoinSub($embeddings, 'embedding_counts', 'embedding_counts.sam_opportunity_document_id', '=', 'sam_opportunity_documents.id')
            ->groupBy('sam_opportunity_id');

        return SamOpportunity::query()
            ->leftJoinSub($documents, 'doc_counts', 'doc_counts.sam_opportunity_id', '=', 'sam_opportunities.id')
            ->leftJoinSub($parsedDocs, 'parsed_counts', 'parsed_counts.sam_opportunity_id', '=', 'sam_opportunities.id')
            ->leftJoinSub($chunksByOpportunity, 'chunk_totals', 'chunk_totals.sam_opportunity_id', '=', 'sam_opportunities.id')
            ->leftJoinSub($embeddingsByOpportunity, 'embedding_totals', 'embedding_totals.sam_opportunity_id', '=', 'sam_opportunities.id')
            ->select('sam_opportunities.*')
            ->selectRaw('((select count(*) from sam_opportunity_favorites where sam_opportunity_id = sam_opportunities.id) + (select count(*) from sam_opportunity_admin_favorites where sam_opportunity_id = sam_opportunities.id)) as favorites_count')
            ->selectRaw('coalesce(doc_counts.documents_count, 0) as documents_count')
            ->selectRaw('coalesce(parsed_counts.parsed_docs, 0) as parsed_docs')
            ->selectRaw('coalesce(chunk_totals.chunks_total, 0) as chunks_count')
            ->selectRaw('coalesce(embedding_totals.embeddings_total, 0) as embeddings_count')
            ->selectRaw($this->pipelineStatusSql());
    }

    protected function pipelineStatusSql(): string
    {
        return "case
            when coalesce(doc_counts.documents_count,0) = 0 then 'empty'
            when coalesce(parsed_counts.parsed_docs,0) >= coalesce(doc_counts.documents_count,0) and coalesce(doc_counts.documents_count,0) > 0 and coalesce(chunk_totals.chunks_total,0) > 0 and coalesce(embedding_totals.embeddings_total,0) >= coalesce(chunk_totals.chunks_total,0) then 'complete'
            when coalesce(parsed_counts.parsed_docs,0) > 0 or coalesce(chunk_totals.chunks_total,0) > 0 or coalesce(embedding_totals.embeddings_total,0) > 0 then 'partial'
            else 'needs_attention'
        end as pipeline_status";
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SamInsightsStats::class,
        ];
    }
}
