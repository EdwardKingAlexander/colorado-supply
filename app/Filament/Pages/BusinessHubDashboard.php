<?php

namespace App\Filament\Pages;

use App\Enums\DocumentStatus;
use App\Filament\Widgets\BusinessHubStatsWidget;
use App\Models\BusinessDeadline;
use App\Models\BusinessDocument;
use App\Models\BusinessLink;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use UnitEnum;

class BusinessHubDashboard extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static UnitEnum|string|null $navigationGroup = 'Business Hub';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'Business Hub';

    protected string $view = 'filament.pages.business-hub-dashboard';

    public function getOverdueDeadlines(): Collection
    {
        return BusinessDeadline::overdue()
            ->orderBy('due_date')
            ->limit(10)
            ->get();
    }

    public function getUpcomingDeadlines(): Collection
    {
        return BusinessDeadline::upcoming(30)
            ->orderBy('due_date')
            ->limit(10)
            ->get();
    }

    public function getExpiringDocuments(): Collection
    {
        return BusinessDocument::expiringSoon(60)
            ->orderBy('expiration_date')
            ->limit(10)
            ->get();
    }

    public function getExpiredDocuments(): Collection
    {
        return BusinessDocument::where('status', DocumentStatus::Expired)
            ->orWhere(function ($query) {
                $query->whereNotNull('expiration_date')
                    ->where('expiration_date', '<', now());
            })
            ->orderBy('expiration_date', 'desc')
            ->limit(5)
            ->get();
    }

    public function getQuickLinks(): Collection
    {
        return BusinessLink::active()
            ->ordered()
            ->get()
            ->groupBy(fn ($link) => $link->category->label());
    }

    public function hasAlerts(): bool
    {
        return $this->getOverdueDeadlines()->isNotEmpty()
            || $this->getExpiredDocuments()->isNotEmpty();
    }

    public function hasWarnings(): bool
    {
        return $this->getUpcomingDeadlines()->where('due_date', '<=', now()->addDays(14))->isNotEmpty()
            || $this->getExpiringDocuments()->where('expiration_date', '<=', now()->addDays(30))->isNotEmpty();
    }

    protected function getHeaderWidgets(): array
    {
        return [
            BusinessHubStatsWidget::class,
        ];
    }
}
