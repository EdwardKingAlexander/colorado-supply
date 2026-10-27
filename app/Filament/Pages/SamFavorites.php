<?php

namespace App\Filament\Pages;

use App\Models\Admin;
use App\Models\SamOpportunity;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use UnitEnum;

class SamFavorites extends Page implements HasTable
{
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-star';

    protected static UnitEnum|string|null $navigationGroup = 'SAM';

    protected static ?string $navigationLabel = 'SAM Favorites';

    protected static ?string $title = 'SAM Favorites';

    protected string $view = 'filament.pages.sam-favorites';

    #[Locked]
    public ?int $focus = null;

    public static function canAccess(): bool
    {
        return Auth::guard('admin')->user() instanceof Admin;
    }

    public function mount(): void
    {
        $this->admin();
        $focus = request()->query('focus');
        if ($focus !== null) {
            abort_unless(is_scalar($focus) && ctype_digit((string) $focus) && (int) $focus > 0, 404);
            $this->focus = SamOpportunity::findOrFail((int) $focus)->id;
        }
    }

    protected function admin(): Admin
    {
        $admin = Auth::guard('admin')->user();
        abort_unless($admin instanceof Admin, 403);

        return $admin;
    }

    #[Computed]
    public function focusedOpportunity(): ?SamOpportunity
    {
        $adminId = $this->admin()->id;

        return $this->focus ? SamOpportunity::query()
            ->withCount('documents')
            ->withExists(['favoritedByAdmins as is_favorite' => fn (Builder $query) => $query->whereKey($adminId)])
            ->findOrFail($this->focus) : null;
    }

    public function favorite(int $id): void
    {
        $admin = $this->admin();
        $opportunity = SamOpportunity::findOrFail($id);
        $admin->favoriteSamOpportunities()->syncWithoutDetaching([$opportunity->id]);
        unset($this->focusedOpportunity);
        $this->resetTable();
    }

    public function unfavorite(int $id): void
    {
        $this->admin()->favoriteSamOpportunities()->detach($id);
        unset($this->focusedOpportunity);
        $this->resetTable();
    }

    public static function sourceUrl(SamOpportunity $opportunity): ?string
    {
        $url = $opportunity->url;
        $host = strtolower(parse_url($url ?? '', PHP_URL_HOST) ?? '');

        return in_array(parse_url($url ?? '', PHP_URL_SCHEME), ['https', 'http'], true)
            && ($host === 'sam.gov' || str_ends_with($host, '.sam.gov')) ? $url : null;
    }

    public function table(Table $table): Table
    {
        $adminId = $this->admin()->id;

        return $table
            ->query(SamOpportunity::query()->whereHas('favoritedByAdmins', fn (Builder $query) => $query->whereKey($adminId))->withCount('documents'))
            ->columns([
                TextColumn::make('title')->searchable()->wrap(),
                TextColumn::make('agency')->searchable()->wrap(),
                TextColumn::make('solicitation_number')->label('Solicitation')->searchable(),
                TextColumn::make('response_deadline')->label('Due')->date()->sortable(),
                TextColumn::make('documents_count')->label('Documents'),
            ])
            ->recordUrl(fn (SamOpportunity $record) => static::getUrl(['focus' => $record->id], panel: 'admin'))
            ->actions([
                Action::make('open')->label('Open opportunity')->url(fn (SamOpportunity $record) => static::getUrl(['focus' => $record->id], panel: 'admin')),
                Action::make('unfavorite')->label('Remove favorite')->icon('heroicon-o-star')->action(fn (SamOpportunity $record) => $this->unfavorite($record->id)),
            ])
            ->defaultSort('response_deadline')
            ->emptyStateHeading('No favorites yet')
            ->emptyStateDescription('Open an opportunity from SAM Insights and select Add to favorites.');
    }
}
