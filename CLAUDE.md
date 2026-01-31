# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Colorado Supply** is a multi-tenant B2B supply chain and federal contracting platform with three core domains:
- **E-Commerce Store**: Product catalog, multi-location inventory, quotes, and orders
- **Sales/CRM Pipeline**: Customer opportunity tracking with pipelines and deal forecasting
- **Federal Contracting Intelligence**: SAM.gov opportunity monitoring, document parsing, and compliance auditing

## Common Commands

```bash
# Development server (runs Laravel, queue worker, and Vite concurrently)
composer run dev

# Run tests
php artisan test                              # All tests
php artisan test tests/Feature/ExampleTest.php  # Single file
php artisan test --filter=testName            # Filter by name

# Code formatting (run before committing)
vendor/bin/pint --dirty

# Build frontend assets
npm run build
npm run dev        # Dev server with HMR

# Generate files
php artisan make:model ModelName -mfs --no-interaction  # Model with migration, factory, seeder
php artisan make:test TestName --pest --no-interaction  # Pest feature test
php artisan make:livewire Component/Name --no-interaction
php artisan make:volt ComponentName --test --pest --no-interaction
```

## Architecture Overview

### Multi-Tenancy
- **CompanyScope**: Global scope filters records by user's company automatically
- **Location-Based Inventory**: Products have location-specific stock via `LocationProducts` pivot
- Admin users bypass company scope for cross-tenant operations

### Tech Stack
- **Backend**: Laravel 12, Livewire 3, Filament v4 (admin panel)
- **Frontend**: Vue 3 + Inertia.js v2 (customer portal), Flux UI Free (Livewire components)
- **Auth**: Sanctum v4, Spatie Permissions (`web` and `admin` guards)
- **AI Integration**: Laravel MCP v0 with two custom servers

### Core Data Flow
```
E-Commerce:  Product Catalog → Quote → Order → Payment → Shipment
Federal:     SAM.gov API → Opportunities → User Favorites → Documents → Parse → Embeddings → RAG Query
```

### Key Model Relationships
- `Company` → `Locations` → `LocationProducts` ← `Products`
- `Customer` → `Opportunities` → `Activities`, `Attachments`
- `Quote` → `QuoteItems` → `Order` → `OrderItems`, `Payments`, `Shipments`
- `SamOpportunity` → `SamOpportunityDocuments` → `Chunks`, `Embeddings`
- `Vendor` tracks compliance: SAM expiration, W-9, Insurance, CAGE, DUNS, NAICS

### Key Enums
Located in `app/Enums/`: `OrderStatus`, `PaymentStatus`, `FulfillmentStatus`, `PaymentMethod`

## Directory Structure

| Directory | Purpose |
|-----------|---------|
| `app/Filament/Resources/` | 70+ Filament CRUD resources |
| `app/Filament/Pages/` | Custom pages (FetchSamControlPanel, SamInsightsDashboard, McpDashboard) |
| `app/Services/` | Business logic (SamApiClient, QuoteOrderingService, CuiDetectionService) |
| `app/Mcp/Servers/` | Two MCP servers: Business and ChromeDevTools |
| `app/Http/Controllers/Api/V1/` | RESTful API controllers |
| `resources/js/Pages/` | Vue pages (Auth/, Store/, Sam/, Profile/) |
| `resources/js/Stores/` | Pinia stores (useQuoteStore for cart) |
| `routes/ai.php` | MCP server registration |

## MCP Servers

Registered in `routes/ai.php`, accessible at `/mcp/business` and `/mcp/chrome-devtools`:

**BusinessServer** (`app/Mcp/Servers/Business/`):
- `FetchSamOpportunitiesTool`: Fetch federal opportunities from SAM.gov
- `ComplianceAuditTool`: Audit vendor compliance documents
- `FetchCompetitorPricingTool`: Compare competitor pricing

**ChromeDevToolsServer** (`app/Mcp/Servers/ChromeDevTools/`):
- Browser automation tools: `NavigateTool`, `ScreenshotTool`, `EvaluateScriptTool`
- `VendorPortalLoginTool`, `ProductDataScraperTool`

## API Structure (v1)

Prefix: `/api/v1/`, authentication via Sanctum

**Public**: `/store/categories`, `/store/products`, `/store/search`, `/store/quote`

**Authenticated**:
- SAM: `/sam-opportunities/favorites`, `/{id}/favorite`, `/{id}/documents`, `/{id}/rag-query`
- Export: `/sam-opportunities/export` (throttled: 10/60s)

## Frontend Aliases

Defined in `vite.config.js`:
- `@` → `resources/js/`
- `@images` → `resources/images/`

## Testing Patterns

```php
// Filament tests use livewire()
livewire(ListUsers::class)
    ->assertCanSeeTableRecords($users)
    ->searchTable($users->first()->name);

// Volt component tests
Volt::test('pages.products.create')
    ->actingAs($user)
    ->set('form.name', 'Test')
    ->call('create')
    ->assertHasNoErrors();

// API tests
$this->actingAs($user)
    ->postJson('/api/v1/store/quote', $data)
    ->assertSuccessful();
```

## Key Configuration

- `config/sam_opportunities.php`: SAM.gov API settings, NAICS codes, notice types
- `config/scraping.php`: Chrome/browser automation parameters
- `bootstrap/app.php`: Middleware, exceptions, routing (Laravel 12 structure)

## Filament v4 Notes

- Layout components (`Grid`, `Section`, `Fieldset`) moved to `Filament\Schemas\Components`
- All actions extend `Filament\Actions\Action` (no `Filament\Tables\Actions`)
- Icons use `Filament\Support\Icons\Heroicon` enum
- `deferFilters()` is now default behavior; use `deferFilters(false)` to disable
