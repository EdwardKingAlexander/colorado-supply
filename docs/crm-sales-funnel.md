# CRM Sales Funnel - Implementation Guide

## Overview

This document describes the CRM Sales Funnel system that has been partially implemented for the Colorado Supply application. The system provides a complete sales pipeline management solution with opportunities, customers, stages, and forecasting capabilities.

## What Has Been Implemented

### 1. Database Schema (✅ COMPLETE)

All database migrations have been created and run successfully:

- **customers** - Customer/prospect records with contact information
- **pipelines** - Multiple sales pipelines support
- **stages** - Pipeline stages with probability defaults and forecast categories
- **opportunities** - Sales opportunities/deals
- **lost_reasons** - Predefined reasons for lost deals
- **activities** - Tasks, calls, meetings, notes attached to opportunities
- **attachments** - File attachments for opportunities

#### Key Features:
- Soft deletes on all main tables
- Comprehensive indexing for performance
- Foreign key constraints with cascade deletes
- Support for multiple currencies
- Stage-based probability defaults with per-deal overrides

### 2. Eloquent Models (✅ COMPLETE)

All models have been created with full relationships:

#### Customer Model
- Fields: name, email, phone, company, website, billing/shipping addresses
- Relationships: belongsTo User (owner), hasMany Opportunities

#### Pipeline Model
- Fields: name, description, is_default, position
- Relationships: hasMany Stages, hasMany Opportunities

#### Stage Model
- Fields: name, position, probability_default, forecast_category, is_won, is_lost
- Relationships: belongsTo Pipeline, hasMany Opportunities
- Forecast categories: Pipeline, BestCase, Commit, Closed

#### Opportunity Model ⭐ (CORE MODEL)
- **All standard fields**: customer_id, pipeline_id, stage_id, title, description, amount, currency
- **Sales fields**: probability_override, expected_close_date, status, source, score
- **Tracking fields**: owner_id, created_by, updated_by, lost_reason_id, closed_at
- **Computed accessors**:
  - `probability_effective`: Returns override if set, else stage default
  - `forecast_amount`: Calculates weighted forecast (amount × probability / 100)
- **Relationships**: customer, pipeline, stage, owner, createdBy, updatedBy, lostReason, activities, attachments

#### Activity Model
- Types: call, email, meeting, task, note
- Fields: subject, body, due_at, done_at, owner_id

#### Attachment Model
- Fields: path, original_name, size, mime, uploaded_by

#### LostReason Model
- Fields: label, description, active

### 3. Sample Data Seeder (✅ COMPLETE)

**File**: `database/seeders/CRMSeeder.php`

Creates a complete demo environment:
- Default "Standard Sales" pipeline with 6 stages:
  1. Qualification (10% probability)
  2. Discovery (25%)
  3. Proposal (50% - BestCase)
  4. Negotiation (70% - Commit)
  5. Closed Won (100% - is_won=true)
  6. Closed Lost (0% - is_lost=true)
- 5 Lost Reasons (Budget, No Decision, Competitor, Timing, No Response)
- 5 Sample Customers
- 6 Sample Opportunities across different stages
- Sample activities for open opportunities

**To run**: `php artisan db:seed --class=CRMSeeder`

## What Needs To Be Implemented

### 1. Filament Resources (🔴 TODO)

Create Filament resources under the "CRM" navigation group:

#### CustomerResource
```bash
php artisan make:filament-resource CRM/Customer --generate
```

**Configuration**:
- Navigation group: 'CRM'
- Navigation icon: 'heroicon-o-user-group'
- Form fields: name, email, phone, company, website, owner
- Table columns: name, company, email, phone, owner, opportunities_count
- Filters: owner, created date range
- Relation managers: OpportunitiesRelationManager

#### PipelineResource
```bash
php artisan make:filament-resource CRM/Pipeline --generate
```

**Configuration**:
- Navigation group: 'CRM'
- Navigation icon: 'heroicon-o-queue-list'
- Form fields: name, description, is_default, position
- Sortable by position
- Relation managers: StagesRelationManager (with drag-drop sorting)

#### OpportunityResource ⭐ (MOST IMPORTANT)
```bash
php artisan make:filament-resource CRM/Opportunity --generate
```

**Form Configuration**:
```php
Forms\Components\Section::make('Opportunity Details')
    ->schema([
        Forms\Components\Select::make('customer_id')
            ->relationship('customer', 'name')
            ->required()
            ->searchable()
            ->createOptionForm([ /* inline customer creation */ ]),

        Forms\Components\Select::make('pipeline_id')
            ->relationship('pipeline', 'name')
            ->required()
            ->live(),

        Forms\Components\Select::make('stage_id')
            ->relationship('stage', 'name', fn($query, $get) =>
                $query->where('pipeline_id', $get('pipeline_id'))
            )
            ->required(),

        Forms\Components\TextInput::make('title')->required(),
        Forms\Components\Textarea::make('description')->rows(3),
    ]),

Forms\Components\Section::make('Financial')
    ->schema([
        Forms\Components\TextInput::make('amount')
            ->numeric()
            ->prefix('$')
            ->required(),

        Forms\Components\Select::make('currency')
            ->options(['USD' => 'USD', 'CAD' => 'CAD'])
            ->default('USD'),

        Forms\Components\TextInput::make('probability_override')
            ->numeric()
            ->suffix('%')
            ->minValue(0)
            ->maxValue(100)
            ->helperText('Override stage default probability'),
    ]),

Forms\Components\Section::make('Timeline & Ownership')
    ->schema([
        Forms\Components\DatePicker::make('expected_close_date'),
        Forms\Components\Select::make('owner_id')
            ->relationship('owner', 'name')
            ->required(),
        Forms\Components\Select::make('source')
            ->options([
                'Website' => 'Website',
                'Referral' => 'Referral',
                'Cold Call' => 'Cold Call',
                'Trade Show' => 'Trade Show',
                'Direct Mail' => 'Direct Mail',
                'Existing Customer' => 'Existing Customer',
            ]),
        Forms\Components\Select::make('score')
            ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5'])
            ->helperText('Deal priority/quality score'),
    ]),
```

**Table Configuration**:
```php
Tables\Columns\TextColumn::make('title')->searchable(),
Tables\Columns\TextColumn::make('customer.name')->searchable(),
Tables\Columns\TextColumn::make('pipeline.name'),
Tables\Columns\BadgeColumn::make('stage.name')
    ->color(fn ($record) => match(true) {
        $record->stage->is_won => 'success',
        $record->stage->is_lost => 'danger',
        $record->stage->forecast_category === 'Commit' => 'warning',
        default => 'primary',
    }),
Tables\Columns\TextColumn::make('amount')->money('usd'),
Tables\Columns\TextColumn::make('probability_effective')->suffix('%'),
Tables\Columns\TextColumn::make('forecast_amount')->money('usd'),
Tables\Columns\TextColumn::make('expected_close_date')->date(),
Tables\Columns\TextColumn::make('owner.name'),
Tables\Columns\BadgeColumn::make('status'),
```

**Filters**:
- Pipeline (SelectFilter)
- Stage (SelectFilter dependent on pipeline)
- Owner (SelectFilter)
- Status (SelectFilter)
- Amount range (Filter)
- Expected close date (DateRangeFilter)
- Source (SelectFilter)

**Actions**:
- Quick stage change (Action with stage selector)
- Mark Won (Action - sets status=won, closed_at=now)
- Mark Lost (Action - prompts for lost_reason_id, sets status=lost)
- Add Activity (Action opening modal)
- Upload Attachment (Action)

**Relation Managers**:
- ActivitiesRelationManager
- AttachmentsRelationManager

**Widgets on View Page**:
```php
// DealHealthWidget
protected function getHeaderWidgets(): array
{
    return [
        OpportunityResource\Widgets\DealHealthWidget::class,
        OpportunityResource\Widgets\ForecastWidget::class,
    ];
}
```

### 2. Kanban Board Page (🔴 TODO - HIGH PRIORITY)

**File**: `app/Filament/Resources/CRM/OpportunityResource/Pages/KanbanBoard.php`

Create a custom Filament page under OpportunityResource:

```bash
php artisan make:filament-page KanbanBoard --resource=CRM/OpportunityResource
```

**Implementation Requirements**:

#### Backend (Livewire Component)
```php
class KanbanBoard extends Page
{
    protected static string $resource = OpportunityResource::class;
    protected static string $view = 'filament.resources.crm.opportunity-resource.pages.kanban-board';

    public $pipelineId;
    public $stages = [];
    public $opportunities = [];

    public function mount(): void
    {
        $this->pipelineId = Pipeline::where('is_default', true)->first()?->id
            ?? Pipeline::first()->id;
        $this->loadBoard();
    }

    public function loadBoard(): void
    {
        $pipeline = Pipeline::with('stages')->find($this->pipelineId);
        $this->stages = $pipeline->stages()->orderBy('position')->get();

        $this->opportunities = Opportunity::with(['customer', 'owner'])
            ->where('pipeline_id', $this->pipelineId)
            ->where('status', 'open')
            ->get()
            ->groupBy('stage_id');
    }

    public function moveOpportunity($opportunityId, $newStageId): void
    {
        $opportunity = Opportunity::find($opportunityId);
        $newStage = Stage::find($newStageId);

        // Business logic
        if ($newStage->is_won) {
            $opportunity->update([
                'stage_id' => $newStageId,
                'status' => 'won',
                'closed_at' => now(),
            ]);
        } elseif ($newStage->is_lost) {
            // Prompt for lost reason via modal
            $this->dispatch('promptLostReason', $opportunityId, $newStageId);
            return;
        } else {
            $opportunity->update(['stage_id' => $newStageId]);
        }

        $this->loadBoard();
        Notification::make()->success()->title('Opportunity moved')->send();
    }
}
```

#### Frontend (Blade + Alpine + SortableJS)

**Install SortableJS**:
```bash
npm install sortablejs
```

**View Template** (`resources/views/filament/resources/crm/opportunity-resource/pages/kanban-board.blade.php`):

```blade
<x-filament-panels::page>
    <div class="space-y-4">
        {{-- Pipeline Selector --}}
        <div class="flex items-center justify-between">
            <select wire:model.live="pipelineId" class="...">
                @foreach(\\App\\Models\\Pipeline::all() as $pipeline)
                    <option value="{{ $pipeline->id }}">{{ $pipeline->name }}</option>
                @endforeach
            </select>

            <div class="flex gap-4">
                <div class="text-sm">
                    <span class="font-semibold">Total Pipeline:</span>
                    <span class="text-lg">${{ number_format($totalValue) }}</span>
                </div>
                <div class="text-sm">
                    <span class="font-semibold">Forecast:</span>
                    <span class="text-lg">${{ number_format($forecastValue) }}</span>
                </div>
            </div>
        </div>

        {{-- Kanban Columns --}}
        <div class="grid grid-cols-{{ count($stages) }} gap-4 overflow-x-auto">
            @foreach($stages as $stage)
                <div class="flex flex-col min-w-[280px]"
                     x-data="kanbanColumn({{ $stage->id }})"
                     x-init="initSortable()">

                    {{-- Column Header --}}
                    <div class="bg-gray-100 dark:bg-gray-800 p-3 rounded-t-lg">
                        <h3 class="font-semibold">{{ $stage->name }}</h3>
                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                            {{ $opportunities->get($stage->id)?->count() ?? 0 }} deals
                            · ${{ number_format($opportunities->get($stage->id)?->sum('amount') ?? 0) }}
                        </div>
                    </div>

                    {{-- Cards Container --}}
                    <div class="flex-1 bg-gray-50 dark:bg-gray-900 p-2 rounded-b-lg space-y-2 sortable-container"
                         data-stage-id="{{ $stage->id }}">
                        @foreach($opportunities->get($stage->id) ?? [] as $opportunity)
                            <div class="bg-white dark:bg-gray-800 p-3 rounded shadow cursor-move"
                                 data-opportunity-id="{{ $opportunity->id }}">
                                <div class="font-medium">{{ $opportunity->title }}</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $opportunity->customer->name }}
                                </div>
                                <div class="flex items-center justify-between mt-2">
                                    <span class="font-semibold text-green-600">
                                        ${{ number_format($opportunity->amount) }}
                                    </span>
                                    <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">
                                        {{ $opportunity->probability_effective }}%
                                    </span>
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    {{ $opportunity->expected_close_date?->format('M d') }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script>
        function kanbanColumn(stageId) {
            return {
                initSortable() {
                    const el = this.$el.querySelector('.sortable-container');

                    Sortable.create(el, {
                        group: 'opportunities',
                        animation: 150,
                        onEnd: (evt) => {
                            const opportunityId = evt.item.dataset.opportunityId;
                            const newStageId = evt.to.dataset.stageId;

                            @this.call('moveOpportunity', opportunityId, newStageId);
                        }
                    });
                }
            }
        }
    </script>
    @endpush
</x-filament-panels::page>
```

**Add to Resource Navigation**:
```php
// In OpportunityResource.php
public static function getPages(): array
{
    return [
        'index' => Pages\ListOpportunities::route('/'),
        'create' => Pages\CreateOpportunity::route('/create'),
        'edit' => Pages\EditOpportunity::route('/{record}/edit'),
        'board' => Pages\KanbanBoard::route('/board'), // Add this
    ];
}

protected static function getNavigationItems(): array
{
    return [
        ...parent::getNavigationItems(),
        NavigationItem::make('Board')
            ->icon('heroicon-o-view-columns')
            ->url(static::getUrl('board'))
            ->group(static::getNavigationGroup()),
    ];
}
```

### 3. Dashboard Widgets (🔴 TODO)

Create widgets to display on the dashboard:

#### Pipeline Overview Widget
```bash
php artisan make:filament-widget PipelineOverview --stats-overview
```

Shows:
- Total open opportunities
- Total pipeline value
- Total forecast value
- Win rate (last 90 days)
- Average deal size

#### Forecast by Stage Widget
```bash
php artisan make:filament-widget ForecastByStage --chart
```

Bar chart showing forecast amount by stage

#### Top Opportunities Widget
```bash
php artisan make:filament-widget TopOpportunities --table
```

Table of top 10 opportunities by forecast amount

### 4. Policies & Permissions (🔴 TODO)

**Install Spatie Permission** (optional but recommended):
```bash
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
```

**Create Policies**:
```bash
php artisan make:policy CustomerPolicy --model=Customer
php artisan make:policy OpportunityPolicy --model=Opportunity
php artisan make:policy PipelinePolicy --model=Pipeline
```

**Roles** to create:
- `admin` - Full access
- `sales_manager` - Manage pipelines/stages, all opportunities, reassign owners
- `sales_rep` - CRUD opportunities they own, view board
- `viewer` - Read-only access

**Permission Examples**:
```php
// OpportunityPolicy
public function update(User $user, Opportunity $opportunity): bool
{
    if ($user->hasRole('admin') || $user->hasRole('sales_manager')) {
        return true;
    }

    return $user->hasRole('sales_rep') && $opportunity->owner_id === $user->id;
}
```

### 5. Reports Page (🔴 TODO)

Create a dedicated reports page:

```bash
php artisan make:filament-page CRM/Reports
```

Include:
- Pipeline value by stage (chart)
- Forecast by month/quarter (chart)
- Win rate trends (chart)
- Conversion rates per stage (table)
- Sales cycle length (metric)
- Owner leaderboard (table)
- Export to CSV functionality

### 6. Business Logic - Observers (🔴 TODO)

Create observers to handle business rules:

```bash
php artisan make:observer OpportunityObserver --model=Opportunity
```

**OpportunityObserver.php**:
```php
class OpportunityObserver
{
    public function updating(Opportunity $opportunity): void
    {
        // Track stage changes
        if ($opportunity->isDirty('stage_id')) {
            $oldStage = Stage::find($opportunity->getOriginal('stage_id'));
            $newStage = $opportunity->stage;

            // Log stage change as activity
            Activity::create([
                'opportunity_id' => $opportunity->id,
                'type' => 'note',
                'subject' => 'Stage Changed',
                'body' => "Moved from {$oldStage->name} to {$newStage->name}",
                'owner_id' => auth()->id(),
                'done_at' => now(),
            ]);

            // Auto-update status for won/lost stages
            if ($newStage->is_won) {
                $opportunity->status = 'won';
                $opportunity->closed_at = now();
            } elseif ($newStage->is_lost) {
                $opportunity->status = 'lost';
                $opportunity->closed_at = now();
            }
        }
    }

    public function creating(Opportunity $opportunity): void
    {
        $opportunity->created_by = auth()->id();
        $opportunity->updated_by = auth()->id();
    }

    public function updating(Opportunity $opportunity): void
    {
        $opportunity->updated_by = auth()->id();
    }
}
```

Register in `AppServiceProvider`:
```php
public function boot(): void
{
    Opportunity::observe(OpportunityObserver::class);
}
```

### 7. Import/Export (🔴 TODO)

Install Filament Excel:
```bash
composer require pxlrbt/filament-excel
```

Add to OpportunityResource:
```php
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

public static function table(Table $table): Table
{
    return $table
        ->bulkActions([
            ExportBulkAction::make(),
        ]);
}
```

## Installation Steps for Fresh Environment

1. **Run Migrations**:
```bash
php artisan migrate
```

2. **Seed Database**:
```bash
php artisan db:seed --class=CRMSeeder
```

3. **Create Filament Resources** (follow sections above)

4. **Install Frontend Dependencies**:
```bash
npm install sortablejs
npm run build
```

5. **Optional - Install Spatie ActivityLog** (for audit trail):
```bash
composer require spatie/laravel-activitylog
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
php artisan migrate
```

Then re-add LogsActivity trait to models (Customer, Pipeline, Stage, Opportunity).

## Testing the Implementation

### Manual Testing Checklist

- [ ] Create a new customer
- [ ] Create a new opportunity
- [ ] Move opportunity between stages on Kanban board
- [ ] Mark opportunity as won
- [ ] Mark opportunity as lost (select reason)
- [ ] Add activity to opportunity
- [ ] Upload attachment to opportunity
- [ ] Filter opportunities by various criteria
- [ ] View forecast calculations
- [ ] Export opportunities to CSV

### Automated Tests (TODO)

Create Pest tests:

```bash
php artisan make:test OpportunityTest --pest
```

**Test Examples**:
```php
test('opportunity calculates forecast correctly', function () {
    $stage = Stage::factory()->create(['probability_default' => 50]);
    $opportunity = Opportunity::factory()->create([
        'stage_id' => $stage->id,
        'amount' => 10000,
        'probability_override' => null,
    ]);

    expect($opportunity->forecast_amount)->toBe(5000.00);
});

test('moving to won stage closes opportunity', function () {
    $wonStage = Stage::factory()->create(['is_won' => true]);
    $opportunity = Opportunity::factory()->create();

    $opportunity->update(['stage_id' => $wonStage->id]);

    expect($opportunity->fresh())
        ->status->toBe('won')
        ->closed_at->not->toBeNull();
});
```

## Architecture Decisions

### Why This Approach?

1. **Stage-based Probabilities**: Industry standard (Salesforce, HubSpot) for accurate forecasting
2. **Separate Pipelines**: Allows different sales processes (B2B vs B2G vs channel sales)
3. **Soft Deletes**: Data preservation for compliance and reporting
4. **Activity Logging**: Full audit trail of opportunity changes
5. **Computed Accessors**: Prevents data duplication while maintaining flexibility

### Performance Considerations

- All foreign keys are indexed
- Composite indexes on frequently queried combinations (pipeline_id + stage_id)
- Eager loading in Kanban board to prevent N+1 queries
- Consider adding database views for complex reports

### Future Enhancements

- Email integration (sync sent/received emails to activities)
- Calendar integration (sync meetings)
- Product/line item support on opportunities
- Quote generation
- Territory management
- Revenue recognition and forecasting by month
- Email templates and campaigns
- Lead scoring automation
- Sales analytics dashboards

## Support & Documentation

For Filament-specific documentation:
- https://filamentphp.com/docs

For drag-and-drop implementation:
- SortableJS: https://github.com/SortableJS/Sortable

## Summary

### ✅ Completed
- Database schema with all tables and relationships
- Eloquent models with computed accessors
- Sample data seeder
- Migrations executed successfully

### 🔴 Next Steps
1. Create Filament resources (Customer, Pipeline, Opportunity)
2. Implement Kanban board page with drag-and-drop
3. Create dashboard widgets
4. Implement business logic observers
5. Add policies and permissions
6. Create reports page
7. Write tests

### Estimated Time to Complete
- Filament Resources: 3-4 hours
- Kanban Board: 2-3 hours
- Widgets & Reports: 2 hours
- Policies & Observers: 1 hour
- Testing: 1-2 hours

**Total: ~10-12 hours of development**

This foundation provides all the database structure and models needed. The next phase focuses on building the Filament UI layer on top of this solid backend.
