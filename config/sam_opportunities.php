<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default SAM.gov Search Parameters
    |--------------------------------------------------------------------------
    |
    | These are the default search parameters that Colorado Supply & Procurement LLC
    | should use when querying SAM.gov or any internal procurement-opportunity tools.
    | They should be applied automatically unless the user manually overrides them.
    |
    */

    'naics_codes' => [
        'primary' => [
            '423840', // Industrial Supplies Merchant Wholesalers
            '423710', // Hardware Merchant Wholesalers
            '423720', // Plumbing and Heating Equipment and Supplies (Hydronics) Merchant Wholesalers
            '423830', // Industrial Machinery and Equipment Merchant Wholesalers
            '423850', // Service Establishment Equipment and Supplies Merchant Wholesalers
            '423610', // Electrical Apparatus and Equipment, Wiring Supplies, and Related Equipment Merchant Wholesalers
            '333991', // Power-Driven Handtool Manufacturing
            '333992', // Welding and Soldering Equipment Manufacturing
            '339113', // Surgical Appliance and Supplies Manufacturing
        ],
        'secondary' => [
            '423390', // Other Construction Material Merchant Wholesalers
            '423450', // Medical, Dental, and Hospital Equipment and Supplies Merchant Wholesalers
            '334418', // Printed Circuit Assembly (Electronic Assembly) Manufacturing
            '325612', // Polish and Other Sanitation Good Manufacturing
            '335110', // Electric Lamp Bulb and Part Manufacturing
            '335122', // Commercial, Industrial, and Institutional Electric Lighting Fixture Manufacturing
            '339920', // Sporting and Athletic Goods Manufacturing
            '339999', // All Other Miscellaneous Manufacturing
            '332999', // All Other Miscellaneous Fabricated Metal Product Manufacturing
            '423990', // Other Miscellaneous Durable Goods Merchant Wholesalers
            '425120', // Wholesale Trade Agents and Brokers
            '444130', // Hardware Stores
            '454390', // Other Direct Selling Establishments
        ],
    ],

    'naics_descriptions' => [
        '423840' => 'Industrial Supplies Merchant Wholesalers',
        '423710' => 'Hardware Merchant Wholesalers',
        '423720' => 'Plumbing and Heating Equipment and Supplies (Hydronics) Merchant Wholesalers',
        '423830' => 'Industrial Machinery and Equipment Merchant Wholesalers',
        '423850' => 'Service Establishment Equipment and Supplies Merchant Wholesalers',
        '423610' => 'Electrical Apparatus and Equipment, Wiring Supplies, and Related Equipment Merchant Wholesalers',
        '333991' => 'Power-Driven Handtool Manufacturing',
        '333992' => 'Welding and Soldering Equipment Manufacturing',
        '339113' => 'Surgical Appliance and Supplies Manufacturing',
        '423390' => 'Other Construction Material Merchant Wholesalers',
        '423450' => 'Medical, Dental, and Hospital Equipment and Supplies Merchant Wholesalers',
        '334418' => 'Printed Circuit Assembly (Electronic Assembly) Manufacturing',
        '325612' => 'Polish and Other Sanitation Good Manufacturing',
        '335110' => 'Electric Lamp Bulb and Part Manufacturing',
        '335122' => 'Commercial, Industrial, and Institutional Electric Lighting Fixture Manufacturing',
        '339920' => 'Sporting and Athletic Goods Manufacturing',
        '339999' => 'All Other Miscellaneous Manufacturing',
        '332999' => 'All Other Miscellaneous Fabricated Metal Product Manufacturing',
        '423990' => 'Other Miscellaneous Durable Goods Merchant Wholesalers',
        '425120' => 'Wholesale Trade Agents and Brokers',
        '444130' => 'Hardware Stores',
        '454390' => 'Other Direct Selling Establishments',
    ],

    'psc_codes' => [
        '5340', '5305', '5310', '5325', '5330', '5350',
        '5110', '5120', '5130', '5180',
        '5975', '5925', '5930', '5945', '5999',
        '4240', '4245', '8465',
        '3010', '3020', '3030',
        '4460', '4520', '4130',
        '6850', '7930', '7935', '5140', '9150', '8145',
    ],

    'keywords' => [
        'Industrial supplies', 'MRO', 'consumables', 'hardware', 'fasteners', 'bearings', 'gaskets', 'valves', 'filters', 'abrasives', 'cutting tools', 'shop supplies',
        'Electrical parts', 'connectors', 'circuit breaker', 'relay', 'wiring', 'lighting',
        'Hand tools', 'tool set', 'impact sockets', 'torque wrench', 'welding supplies',
        'PPE', 'gloves', 'respirator', 'safety gear', 'first aid',
        'Janitorial', 'cleaning supplies', 'disinfectant',
        'Fort Carson', 'Peterson AFB', 'Schriever', 'Buckley', 'Cheyenne Mountain', 'Air Force', 'Army', 'DoD',
    ],

    'filters' => [
        'notice_types' => [
            'Combined Synopsis/Solicitation',
            'Solicitation',
            'Sources Sought',
            'Presolicitation',
        ],
        'set_asides' => [
            'SB', 'WOSB', 'EDWOSB', 'SDVOSB', '8(a)', 'HUBZone',
        ],
        'place_of_performance' => 'CO',
    ],

    'search_logic' => [
        'order' => ['naics', 'psc', 'keywords', 'filters'],
        'sort_by' => 'newest',
        'fallback' => 'nationwide_keyword_only',
    ],

    'output_requirements' => [
        'title', 'id', 'agency', 'naics_psc', 'summary', 'dates', 'location', 'link', 'match_strength',
    ],

    'error_handling' => [
        'incomplete_entries' => 'keep',
        'flag_missing' => ['naics', 'psc'],
        'highlight_incomplete' => true,
    ],

    'refresh_interval' => [
        'default' => '6 hours',
        'options' => ['15 minutes', '30 minutes', '1 hour', '2 hours', '4 hours', '6 hours', '12 hours', '24 hours'],
    ],
];
