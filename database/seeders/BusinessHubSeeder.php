<?php

namespace Database\Seeders;

use App\Enums\DeadlineCategory;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\LinkCategory;
use App\Enums\RecurrenceType;
use App\Models\BusinessDeadline;
use App\Models\BusinessDocument;
use App\Models\BusinessLink;
use Illuminate\Database\Seeder;

class BusinessHubSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedDefaultLinks();
        $this->seedSampleDocuments();
        $this->seedDefaultDeadlines();
    }

    private function seedDefaultLinks(): void
    {
        $links = [
            // Federal
            [
                'name' => 'IRS Business Portal',
                'url' => 'https://www.irs.gov/businesses',
                'category' => LinkCategory::Federal,
                'description' => 'Federal tax information and filing',
                'icon' => 'heroicon-o-building-library',
                'sort_order' => 1,
            ],
            [
                'name' => 'SAM.gov',
                'url' => 'https://sam.gov',
                'category' => LinkCategory::Federal,
                'description' => 'System for Award Management - Federal contracting',
                'icon' => 'heroicon-o-star',
                'sort_order' => 2,
            ],
            [
                'name' => 'SBA.gov',
                'url' => 'https://www.sba.gov',
                'category' => LinkCategory::Federal,
                'description' => 'Small Business Administration resources',
                'icon' => 'heroicon-o-briefcase',
                'sort_order' => 3,
            ],
            [
                'name' => 'EFTPS',
                'url' => 'https://www.eftps.gov',
                'category' => LinkCategory::Federal,
                'description' => 'Electronic Federal Tax Payment System',
                'icon' => 'heroicon-o-credit-card',
                'sort_order' => 4,
            ],

            // State
            [
                'name' => 'Colorado Secretary of State',
                'url' => 'https://www.sos.state.co.us',
                'category' => LinkCategory::State,
                'description' => 'Business filings and annual reports',
                'icon' => 'heroicon-o-document-text',
                'sort_order' => 10,
            ],
            [
                'name' => 'Colorado Department of Revenue',
                'url' => 'https://tax.colorado.gov',
                'category' => LinkCategory::State,
                'description' => 'State tax filing and payments',
                'icon' => 'heroicon-o-currency-dollar',
                'sort_order' => 11,
            ],
            [
                'name' => 'MyBizColorado',
                'url' => 'https://mybiz.colorado.gov',
                'category' => LinkCategory::State,
                'description' => 'One-stop shop for Colorado business needs',
                'icon' => 'heroicon-o-building-storefront',
                'sort_order' => 12,
            ],
            [
                'name' => 'Revenue Online',
                'url' => 'https://www.colorado.gov/revenueonline',
                'category' => LinkCategory::State,
                'description' => 'File and pay Colorado taxes online',
                'icon' => 'heroicon-o-computer-desktop',
                'sort_order' => 13,
            ],

            // Federal - Additional
            [
                'name' => 'USASpending.gov',
                'url' => 'https://www.usaspending.gov',
                'category' => LinkCategory::Federal,
                'description' => 'Track federal spending and contracts',
                'icon' => 'heroicon-o-chart-bar',
                'sort_order' => 5,
            ],
            [
                'name' => 'GSA Advantage',
                'url' => 'https://www.gsaadvantage.gov',
                'category' => LinkCategory::Federal,
                'description' => 'GSA Schedule ordering system',
                'icon' => 'heroicon-o-shopping-cart',
                'sort_order' => 6,
            ],
            [
                'name' => 'FPDS',
                'url' => 'https://www.fpds.gov',
                'category' => LinkCategory::Federal,
                'description' => 'Federal Procurement Data System',
                'icon' => 'heroicon-o-document-magnifying-glass',
                'sort_order' => 7,
            ],

            // Banking
            [
                'name' => 'Business Bank Login',
                'url' => 'https://www.chase.com/business',
                'category' => LinkCategory::Banking,
                'description' => 'Primary business bank account',
                'icon' => 'heroicon-o-banknotes',
                'sort_order' => 20,
            ],

            // Local
            [
                'name' => 'Denver Business Licensing',
                'url' => 'https://www.denvergov.org/Government/Agencies-Departments-Offices/Business-Licensing',
                'category' => LinkCategory::Local,
                'description' => 'Denver city business licenses',
                'icon' => 'heroicon-o-building-office',
                'sort_order' => 30,
            ],
        ];

        foreach ($links as $link) {
            BusinessLink::firstOrCreate(
                ['url' => $link['url']],
                $link
            );
        }
    }

    private function seedSampleDocuments(): void
    {
        $documents = [
            [
                'type' => DocumentType::TaxDocument,
                'name' => 'EIN Confirmation Letter',
                'description' => 'IRS Employer Identification Number assignment letter',
                'document_number' => null, // User will fill in
                'issuing_authority' => 'Internal Revenue Service',
                'issue_date' => null,
                'expiration_date' => null,
                'status' => DocumentStatus::Active,
            ],
            [
                'type' => DocumentType::License,
                'name' => 'Colorado Sales Tax License',
                'description' => 'State sales tax license for retail and wholesale',
                'document_number' => null,
                'issuing_authority' => 'Colorado Department of Revenue',
                'issue_date' => null,
                'expiration_date' => null,
                'status' => DocumentStatus::Active,
            ],
            [
                'type' => DocumentType::Registration,
                'name' => 'SAM.gov Registration',
                'description' => 'System for Award Management registration for federal contracting',
                'document_number' => null,
                'issuing_authority' => 'System for Award Management',
                'issue_date' => null,
                'expiration_date' => null,
                'status' => DocumentStatus::Active,
                'metadata' => [
                    'uei' => null,
                    'cage_code' => null,
                ],
            ],
            [
                'type' => DocumentType::Registration,
                'name' => 'Colorado LLC Registration',
                'description' => 'Articles of Organization for Colorado Supply, LLC',
                'document_number' => null,
                'issuing_authority' => 'Colorado Secretary of State',
                'issue_date' => null,
                'expiration_date' => null,
                'status' => DocumentStatus::Active,
            ],
            [
                'type' => DocumentType::Insurance,
                'name' => 'General Liability Insurance',
                'description' => 'Commercial general liability insurance policy',
                'document_number' => null,
                'issuing_authority' => null,
                'issue_date' => null,
                'expiration_date' => null,
                'status' => DocumentStatus::Active,
                'metadata' => [
                    'coverage_amount' => null,
                    'carrier' => null,
                ],
            ],
            [
                'type' => DocumentType::Insurance,
                'name' => 'Workers Compensation Insurance',
                'description' => 'Workers compensation coverage for employees',
                'document_number' => null,
                'issuing_authority' => null,
                'issue_date' => null,
                'expiration_date' => null,
                'status' => DocumentStatus::Active,
            ],
            [
                'type' => DocumentType::TaxDocument,
                'name' => 'W-9 Form',
                'description' => 'Request for Taxpayer Identification Number',
                'document_number' => null,
                'issuing_authority' => 'Internal Revenue Service',
                'issue_date' => null,
                'expiration_date' => null,
                'status' => DocumentStatus::Active,
            ],
            [
                'type' => DocumentType::License,
                'name' => 'Business License',
                'description' => 'Local business operating license',
                'document_number' => null,
                'issuing_authority' => null,
                'issue_date' => null,
                'expiration_date' => null,
                'status' => DocumentStatus::Active,
            ],
        ];

        foreach ($documents as $document) {
            BusinessDocument::firstOrCreate(
                ['name' => $document['name']],
                $document
            );
        }
    }

    private function seedDefaultDeadlines(): void
    {
        $deadlines = [
            [
                'title' => 'Federal Quarterly Tax (Form 941)',
                'description' => 'File Form 941 for quarterly federal tax return',
                'category' => DeadlineCategory::Tax,
                'due_date' => $this->getNextQuarterlyDueDate(),
                'recurrence' => RecurrenceType::Quarterly,
                'reminder_days' => [30, 14, 7, 1],
                'external_url' => 'https://www.eftps.gov',
            ],
            [
                'title' => 'Colorado Sales Tax Filing',
                'description' => 'File monthly Colorado sales tax return',
                'category' => DeadlineCategory::Tax,
                'due_date' => now()->addMonth()->startOfMonth()->addDays(19),
                'recurrence' => RecurrenceType::Monthly,
                'reminder_days' => [14, 7, 3, 1],
                'external_url' => 'https://www.colorado.gov/revenueonline',
            ],
            [
                'title' => 'Colorado Annual Report',
                'description' => 'File annual report with Colorado Secretary of State',
                'category' => DeadlineCategory::Registration,
                'due_date' => now()->addYear()->startOfYear()->addMonths(3),
                'recurrence' => RecurrenceType::Annually,
                'reminder_days' => [60, 30, 14, 7],
                'external_url' => 'https://www.sos.state.co.us',
            ],
            [
                'title' => 'SAM.gov Registration Renewal',
                'description' => 'Renew SAM.gov registration for federal contracting eligibility',
                'category' => DeadlineCategory::Registration,
                'due_date' => now()->addYear(),
                'recurrence' => RecurrenceType::Annually,
                'reminder_days' => [60, 30, 14, 7],
                'external_url' => 'https://sam.gov',
            ],
        ];

        foreach ($deadlines as $deadline) {
            BusinessDeadline::firstOrCreate(
                ['title' => $deadline['title']],
                $deadline
            );
        }
    }

    private function getNextQuarterlyDueDate(): \Carbon\Carbon
    {
        $now = now();
        $month = $now->month;

        // Quarterly deadlines: April 30, July 31, October 31, January 31
        if ($month <= 3) {
            return $now->copy()->setMonth(4)->setDay(30);
        } elseif ($month <= 6) {
            return $now->copy()->setMonth(7)->setDay(31);
        } elseif ($month <= 9) {
            return $now->copy()->setMonth(10)->setDay(31);
        } else {
            return $now->copy()->addYear()->setMonth(1)->setDay(31);
        }
    }
}
