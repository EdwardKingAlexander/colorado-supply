<?php

namespace App\Services\NsnSourcing;

use App\Models\NsnSalesContact;
use App\Models\NsnSourcingCase;

/**
 * Default inquiry text. Asks only what the case needs answered; the operator
 * edits it before sending it themselves. Nothing here is sent by the app.
 */
class InquiryTemplate
{
    public static function subject(NsnSourcingCase $case): string
    {
        return "Availability inquiry: NSN {$case->formattedNsn()}, P/N {$case->part_number}";
    }

    public static function body(NsnSourcingCase $case, ?NsnSalesContact $contact = null): string
    {
        $greeting = $contact?->contact_name ? "Hello {$contact->contact_name}," : 'Hello,';
        $item = $case->item_name ? " ({$case->item_name})" : '';

        return <<<TEXT
            {$greeting}

            We are looking for the following item and DLA's PUB LOG lists your company (CAGE {$case->cage_code}) as a reference for it:

            NSN: {$case->formattedNsn()}{$item}
            Part number: {$case->part_number}

            Could you tell us:
            1. Do you currently manufacture this exact part number?
            2. If not, is there a replacement or superseding part number?
            3. Do you sell it directly? If so, what are the minimum order quantity and typical lead time?
            4. If you sell through distributors, which ones are authorized for this part?

            Thank you,
            TEXT;
    }
}
