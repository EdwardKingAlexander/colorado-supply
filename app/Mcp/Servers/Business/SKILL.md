# Business Server Skills

This document tracks reusable skills and patterns for business operations.

## Available Tools

### Competitor Pricing Analysis
Tool: `fetch-competitor-pricing`

**Purpose**: Compare competitor pricing from web scrape data with your internal database pricing.

**Features**:
- Loads scrape data from ChromeDevTools/State/scrape-progress.json
- Matches products by SKU
- Calculates price differences and percentages
- Provides actionable recommendations
- Identifies products not in your catalog
- Highlights pricing opportunities

**Usage Example**:
```json
{
  "min_difference_percent": 5,
  "limit": 50
}
```

**Response Structure**:
```json
{
  "success": true,
  "summary": {
    "total_products": 100,
    "successfully_compared": 85,
    "not_in_database": 10,
    "no_price_set": 5,
    "we_are_cheaper": 45,
    "competitor_cheaper": 40,
    "avg_price_difference_percent": 3.5
  },
  "comparisons": [
    {
      "sku": "ABC-123",
      "title": "Premium Widget",
      "competitor_price": 49.99,
      "our_price": 59.99,
      "difference": 10.00,
      "difference_percent": 20.00,
      "cheaper_source": "competitor",
      "action": "Consider lowering price - competitor is significantly cheaper"
    }
  ]
}
```

### Vendor Compliance Audit
Tool: `compliance-audit`

**Purpose**: Audit vendor compliance documents and identify missing or outdated items.

**Checked Documents**:
- **SAM Registration** - System for Award Management (Annual renewal)
- **W-9 Form** - Tax identification (3-year renewal)
- **Insurance Certificate** - Liability insurance (Annual renewal)
- **CAGE Code** - Commercial and Government Entity Code
- **DUNS Number** - Data Universal Numbering System
- **NAICS Code** - North American Industry Classification System

**Features**:
- Checks database for required compliance fields
- Identifies missing documents
- Flags expired documents
- Warns about expiring documents
- Generates comprehensive compliance report
- Saves report to State/compliance-report.json

**Usage Example (All Vendors)**:
```json
{
  "expiration_warning_days": 30,
  "include_compliant": false
}
```

**Usage Example (Specific Vendor)**:
```json
{
  "vendor_id": 123,
  "expiration_warning_days": 60,
  "include_compliant": true
}
```

**Response Structure**:
```json
{
  "success": true,
  "audit_date": "2025-11-09T12:00:00+00:00",
  "summary": {
    "total_vendors": 50,
    "compliant": 35,
    "non_compliant": 10,
    "expiring_soon": 5,
    "missing_documents": 8
  },
  "vendors": [
    {
      "vendor_id": 123,
      "vendor_name": "Acme Supplies Inc",
      "compliance_status": "non_compliant",
      "missing_count": 2,
      "expired_count": 1,
      "expiring_soon_count": 0,
      "documents": {
        "missing": [
          {
            "document": "W-9 Form",
            "status": "missing",
            "message": "Missing required fields: w9_date, tax_id"
          }
        ],
        "expired": [
          {
            "document": "Insurance Certificate",
            "status": "expired",
            "message": "Expired on 2025-08-15 (3 months ago)",
            "expiration_date": "2025-08-15"
          }
        ],
        "expiring_soon": [],
        "compliant": [
          {
            "document": "SAM Registration",
            "status": "compliant",
            "message": "Valid until 2026-03-15",
            "expiration_date": "2026-03-15",
            "days_until_expiration": 126
          }
        ]
      }
    }
  ]
}
```

**Integration Example**:
```php
use App\Mcp\Servers\Business\Tools\ComplianceAuditTool;

// Run compliance audit for all vendors
$audit = new ComplianceAuditTool();
$report = $audit->execute([
    'expiration_warning_days' => 30,
]);

$results = json_decode($report, true);

// Alert on non-compliant vendors
foreach ($results['vendors'] as $vendor) {
    if ($vendor['compliance_status'] === 'non_compliant') {
        // Send notification to procurement team
        Notification::send(
            User::role('procurement')->get(),
            new ComplianceAlert($vendor)
        );
    }
}

// Report saved to: app/Mcp/Servers/Business/State/compliance-report.json
```

### Federal Contracting Opportunities
Tool: `fetch-sam-opportunities`

**Purpose**: Fetch federal contract opportunities from SAM.gov filtered by NAICS codes, geographic location, and notice types.

**Features**:
- Queries SAM.gov v1 API with multiple NAICS codes in a single request
- Colorado-first strategy with nationwide fallback
- Filters by notice types: Presolicitation, Solicitation, Combined Synopsis/Solicitation
- Maps API response to standardized 12-field schema
- Saves opportunities to State/sam-opportunities.json
- Automatic date range calculation (last N days)

**Configuration**:
Add your SAM.gov API key to `.env`:
```
SAM_API_KEY=SAM-5eae276d-2345-4578-b2d5-06def9bf9c7d
```

**Default NAICS Codes**:
- 423840 - Industrial Supplies Merchant Wholesalers
- 423830 - Industrial Machinery/Equipment Wholesalers
- 423720 - Plumbing/Heating Equipment & Supplies
- 423810 - Construction/Mining Machinery Wholesalers
- 423860 - Transportation Equipment & Supplies
- 423850 - Service Establishment Equipment

**Usage Example (Colorado Industrial Supplies - Last 7 Days)**:
```json
{
  "days_back": 7,
  "state_code": "CO",
  "include_presolicitation": true,
  "limit": 100
}
```

**Usage Example (Nationwide with Custom NAICS)**:
```json
{
  "days_back": 14,
  "naics_codes": ["423840", "423830"],
  "include_presolicitation": false,
  "limit": 50
}
```

**Response Structure**:
```json
{
  "success": true,
  "fetched_at": "2025-11-09T12:00:00+00:00",
  "query": {
    "date_range": "2025-11-02 to 2025-11-09",
    "naics_codes": ["423840", "423830"],
    "state_code": "CO",
    "notice_types": ["Presolicitation", "Solicitation", "Combined Synopsis/Solicitation"]
  },
  "summary": {
    "total_records": 25,
    "returned": 25,
    "limit": 100
  },
  "opportunities": [
    {
      "notice_id": "54ce941a25a14932809b5d83ac52a09a",
      "solicitation_number": "W912DQ-25-R-0001",
      "title": "Industrial Supplies - Fasteners, Bolts, Hardware for Base Maintenance",
      "notice_type": "Solicitation",
      "posted_date": "2025-11-09",
      "response_deadline": "2025-11-30",
      "naics_code": "423840",
      "psc_code": "5340",
      "state_code": "CO",
      "agency_name": "Department of Defense - U.S. Army Corps of Engineers",
      "set_aside_type": "Total Small Business Set-Aside",
      "sam_url": "https://sam.gov/opp/54ce941a25a14932809b5d83ac52a09a/view"
    }
  ]
}
```

**Integration Example**:
```php
use App\Mcp\Servers\Business\Tools\FetchSamOpportunitiesTool;

// Fetch Colorado opportunities (last 7 days)
$samTool = new FetchSamOpportunitiesTool();
$report = $samTool->execute([
    'days_back' => 7,
    'state_code' => 'CO',
    'limit' => 100,
]);

$results = json_decode($report, true);

// Filter opportunities matching our capabilities
foreach ($results['opportunities'] as $opp) {
    if ($opp['naics_code'] === '423840' && $opp['response_deadline']) {
        // Check if we can bid
        $deadline = \Carbon\Carbon::parse($opp['response_deadline']);
        $daysToRespond = $deadline->diffInDays(now());

        if ($daysToRespond >= 14) {
            // Notify sales team of viable opportunity
            Notification::send(
                User::role('sales')->get(),
                new NewOpportunityAlert($opp)
            );
        }
    }
}

// Report saved to: app/Mcp/Servers/Business/State/sam-opportunities.json
```

**Error Handling**:
- Returns error if SAM_API_KEY not configured
- Handles API rate limits (429 responses)
- Gracefully handles missing/null fields in API response
- Logs failures for debugging

**Best Practices**:
- Run daily to catch new opportunities early
- Use Colorado filter first, then nationwide if needed
- Set `days_back` to 7-14 for optimal opportunity freshness
- Monitor response deadlines to ensure adequate proposal time
- Cross-reference NAICS codes with your vendor capabilities

## Available Skills

Skills are saved as PHP files in the `Skills/` directory and can be referenced by agents for complex operations.

### Core Patterns

**Competitive Intelligence Workflow**

Complete workflow combining ChromeDevTools and Business servers:

1. **Login to Competitor Portal** (ChromeDevTools)
2. **Scrape Product Catalog** (ChromeDevTools)
3. **Analyze Pricing** (Business)
4. **Update Database** (Business/Laravel)

Example:
```php
use App\Mcp\Servers\ChromeDevTools\Tools\VendorPortalLoginTool;
use App\Mcp\Servers\ChromeDevTools\Skills\MultiPageScrapeSkill;
use App\Mcp\Servers\Business\Tools\FetchCompetitorPricingTool;

// Step 1: Login
$login = new VendorPortalLoginTool();
$login->execute([
    'url' => 'https://competitor.com/login',
    'username' => config('competitors.username'),
    'password' => config('competitors.password'),
]);

// Step 2: Scrape catalog
$scraper = new MultiPageScrapeSkill();
$urls = MultiPageScrapeSkill::generatePaginatedUrls(
    'https://competitor.com/catalog?page={page}',
    1,
    20
);
$scraper->scrape([
    'urls' => $urls,
    'use_session' => true,
    'delay_ms' => 2000,
]);

// Step 3: Analyze pricing
$pricing = new FetchCompetitorPricingTool();
$analysis = $pricing->execute([
    'min_difference_percent' => 10,
]);

// Step 4: Process results
$results = json_decode($analysis, true);
foreach ($results['comparisons'] as $comparison) {
    if ($comparison['cheaper_source'] === 'competitor'
        && abs($comparison['difference_percent']) > 20) {
        // Alert for significant price differences
        Log::warning("Competitor significantly cheaper", $comparison);
    }
}
```

## Creating New Skills

When you discover a useful pattern:

1. Save the reusable function in `Skills/[SkillName].php`
2. Document it here with:
   - Name and description
   - Usage example
   - Input/output specification
   - Any prerequisites

## State Management

Skills can use the State directory to:
- Cache business data
- Store computation results
- Save intermediate processing state
- Track progress across operations

## Best Practices

- Follow Laravel conventions
- Use Eloquent for database operations
- Handle errors gracefully
- Document any assumptions or requirements
- Log important operations for debugging
