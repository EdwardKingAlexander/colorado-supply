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

    /*
    | NAICS codes are queried one at a time (SAM.gov `ncode` is an exact match).
    | Agencies, DLA especially, post supply buys under the NAICS of the industry
    | that MANUFACTURES the item, so manufacturing codes find far more than the
    | 4238xx wholesaler codes. The additions below come from a 360-day SAM.gov
    | survey (Oct 2026): for each PSC we sell, which NAICS buyers attached.
    | Codes retired in NAICS 2022 (335110, 335122, 444130, 454390) can no longer
    | be posted under and were replaced or removed.
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
            '332919', // Other Metal Valve and Pipe Fitting Manufacturing (valves, fittings)
            '332722', // Bolt, Nut, Screw, Rivet, and Washer Manufacturing (fasteners)
            '332510', // Hardware Manufacturing (misc hardware, PSC 5340)
            '332216', // Saw Blade and Handtool Manufacturing (hand tools, tool kits)
            '335313', // Switchgear and Switchboard Apparatus Manufacturing (breakers, switches)
            '332991', // Ball and Roller Bearing Manufacturing
            '339991', // Gasket, Packing, and Sealing Device Manufacturing
            '335931', // Current-Carrying Wiring Device Manufacturing (connectors, terminals)
            '335929', // Other Communication and Energy Wire Manufacturing (wire and cable)
            '332439', // Other Metal Container Manufacturing (shipping containers, tool boxes)
        ],
        'secondary' => [
            '423390', // Other Construction Material Merchant Wholesalers
            '423450', // Medical, Dental, and Hospital Equipment and Supplies Merchant Wholesalers
            '334418', // Printed Circuit Assembly (Electronic Assembly) Manufacturing
            '325612', // Polish and Other Sanitation Good Manufacturing
            '335139', // Electric Lamp Bulb and Other Lighting Equipment Manufacturing (replaces retired 335110)
            '335132', // Commercial, Industrial, and Institutional Electric Lighting Fixture Manufacturing (replaces retired 335122)
            '339920', // Sporting and Athletic Goods Manufacturing
            '339999', // All Other Miscellaneous Manufacturing
            '332999', // All Other Miscellaneous Fabricated Metal Product Manufacturing
            '423990', // Other Miscellaneous Durable Goods Merchant Wholesalers
            '425120', // Wholesale Trade Agents and Brokers
            '444140', // Hardware Retailers (replaces retired 444130)
            '335314', // Relay and Industrial Control Manufacturing
            '335999', // All Other Miscellaneous Electrical Equipment and Component Manufacturing
            '334417', // Electronic Connector Manufacturing
            '332710', // Machine Shops
            '333612', // Speed Changer, Industrial High-Speed Drive, and Gear Manufacturing (power transmission)
            '326220', // Rubber and Plastics Hoses and Belting Manufacturing
            '332911', // Industrial Valve Manufacturing
            '332912', // Fluid Power Valve and Hose Fitting Manufacturing
            '333998', // All Other Miscellaneous General Purpose Machinery Manufacturing
            '333922', // Conveyor and Conveying Equipment Manufacturing
            '333924', // Industrial Truck, Tractor, Trailer, and Stacker Machinery Manufacturing
            '315990', // Apparel Accessories and Other Apparel Manufacturing (gloves, protective wear)
            '325998', // All Other Miscellaneous Chemical Product and Preparation Manufacturing
            '325510', // Paint and Coating Manufacturing
            '325520', // Adhesive Manufacturing
            '333413', // Industrial and Commercial Fan and Blower and Air Purification Equipment Manufacturing
            '333414', // Heating Equipment (except Warm Air Furnaces) Manufacturing
            '333415', // Air-Conditioning and Warm Air Heating Equipment and Commercial and Industrial Refrigeration Equipment Manufacturing
            '339994', // Broom, Brush, and Mop Manufacturing
            '339112', // Surgical and Medical Instrument Manufacturing
            '334510', // Electromedical and Electrotherapeutic Apparatus Manufacturing
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
        '332919' => 'Other Metal Valve and Pipe Fitting Manufacturing',
        '332722' => 'Bolt, Nut, Screw, Rivet, and Washer Manufacturing',
        '332510' => 'Hardware Manufacturing',
        '332216' => 'Saw Blade and Handtool Manufacturing',
        '335313' => 'Switchgear and Switchboard Apparatus Manufacturing',
        '332991' => 'Ball and Roller Bearing Manufacturing',
        '339991' => 'Gasket, Packing, and Sealing Device Manufacturing',
        '335931' => 'Current-Carrying Wiring Device Manufacturing',
        '335929' => 'Other Communication and Energy Wire Manufacturing',
        '332439' => 'Other Metal Container Manufacturing',
        '423390' => 'Other Construction Material Merchant Wholesalers',
        '423450' => 'Medical, Dental, and Hospital Equipment and Supplies Merchant Wholesalers',
        '334418' => 'Printed Circuit Assembly (Electronic Assembly) Manufacturing',
        '325612' => 'Polish and Other Sanitation Good Manufacturing',
        '335139' => 'Electric Lamp Bulb and Other Lighting Equipment Manufacturing',
        '335132' => 'Commercial, Industrial, and Institutional Electric Lighting Fixture Manufacturing',
        '339920' => 'Sporting and Athletic Goods Manufacturing',
        '339999' => 'All Other Miscellaneous Manufacturing',
        '332999' => 'All Other Miscellaneous Fabricated Metal Product Manufacturing',
        '423990' => 'Other Miscellaneous Durable Goods Merchant Wholesalers',
        '425120' => 'Wholesale Trade Agents and Brokers',
        '444140' => 'Hardware Retailers',
        '335314' => 'Relay and Industrial Control Manufacturing',
        '335999' => 'All Other Miscellaneous Electrical Equipment and Component Manufacturing',
        '334417' => 'Electronic Connector Manufacturing',
        '332710' => 'Machine Shops',
        '333612' => 'Speed Changer, Industrial High-Speed Drive, and Gear Manufacturing',
        '326220' => 'Rubber and Plastics Hoses and Belting Manufacturing',
        '332911' => 'Industrial Valve Manufacturing',
        '332912' => 'Fluid Power Valve and Hose Fitting Manufacturing',
        '333998' => 'All Other Miscellaneous General Purpose Machinery Manufacturing',
        '333922' => 'Conveyor and Conveying Equipment Manufacturing',
        '333924' => 'Industrial Truck, Tractor, Trailer, and Stacker Machinery Manufacturing',
        '315990' => 'Apparel Accessories and Other Apparel Manufacturing',
        '325998' => 'All Other Miscellaneous Chemical Product and Preparation Manufacturing',
        '325510' => 'Paint and Coating Manufacturing',
        '325520' => 'Adhesive Manufacturing',
        '333413' => 'Industrial and Commercial Fan and Blower and Air Purification Equipment Manufacturing',
        '333414' => 'Heating Equipment (except Warm Air Furnaces) Manufacturing',
        '333415' => 'Air-Conditioning and Warm Air Heating Equipment and Commercial and Industrial Refrigeration Equipment Manufacturing',
        '339994' => 'Broom, Brush, and Mop Manufacturing',
        '339112' => 'Surgical and Medical Instrument Manufacturing',
        '334510' => 'Electromedical and Electrotherapeutic Apparatus Manufacturing',
    ],

    /*
    | PSC discovery (ai/modules/sam-psc-discovery). SAM.gov `ccode` is an exact
    | match, and DLA tags its notices with 2-character groups (`53`, not `5305`)
    | while VA/Navy/Army use 4-character PSCs, so both lists are searched.
    |
    | psc_groups: 2-char DLA groups searched by default.
    | psc_groups_optional: offered in the panel, off by default (large, but
    | furthest from a supply distributor's catalog: ship compressor parts,
    | vehicle components).
    */
    'psc_groups' => [
        '53', // Hardware and abrasives
        '59', // Electrical and electronic equipment components
        '48', // Valves
        '47', // Pipe, tubing, hose and fittings
        '61', // Electric wire, power and distribution equipment
        '62', // Lighting fixtures and lamps
        '30', // Mechanical power transmission equipment
        '31', // Bearings
        '41', // Refrigeration, air conditioning and air circulating equipment
        '44', // Furnace, steam plant, drying equipment; air purification
        '45', // Plumbing, heating and waste disposal equipment
        '42', // Firefighting, rescue and safety equipment
        '49', // Maintenance and repair shop equipment
        '39', // Materials handling equipment
        '51', // Hand tools
        '52', // Measuring tools
        '34', // Metalworking machinery (incl. welding)
        '80', // Brushes, paints, sealers and adhesives
        '81', // Containers, packaging and packing supplies
        '84', // Clothing, individual equipment and insignia
        '68', // Chemicals and chemical products
        '79', // Cleaning equipment and supplies
        '91', // Fuels, lubricants, oils and waxes
        '65', // Medical, dental and veterinary equipment and supplies
        '56', // Construction and building materials
    ],

    'psc_groups_optional' => [
        '43', // Pumps and compressors
        '25', // Vehicular equipment components
    ],

    // 4-character PSCs, used by VA/Navy/Army buyers. 3030, 4245 and 7935 were
    // dropped 2026-10-04: zero notices in a 360-day survey.
    'psc_codes' => [
        '5340', '5305', '5310', '5325', '5330', '5350',
        '5110', '5120', '5130', '5180',
        '5975', '5925', '5930', '5945', '5999',
        '4240', '8465',
        '3010', '3020',
        '4460', '4520', '4130',
        '6850', '7930', '5140', '9150', '8145',
    ],

    // Labels for the panel's PSC picker. Codes without an entry show bare.
    'psc_descriptions' => [
        '53' => 'Hardware and Abrasives (DLA group)',
        '59' => 'Electrical and Electronic Components (DLA group)',
        '48' => 'Valves (DLA group)',
        '47' => 'Pipe, Tubing, Hose and Fittings (DLA group)',
        '61' => 'Electric Wire, Power and Distribution Equipment (DLA group)',
        '62' => 'Lighting Fixtures and Lamps (DLA group)',
        '30' => 'Mechanical Power Transmission Equipment (DLA group)',
        '31' => 'Bearings (DLA group)',
        '41' => 'Refrigeration, Air Conditioning and Air Circulating Equipment (DLA group)',
        '44' => 'Furnace, Steam Plant, Drying and Air Purification Equipment (DLA group)',
        '45' => 'Plumbing, Heating and Waste Disposal Equipment (DLA group)',
        '42' => 'Firefighting, Rescue and Safety Equipment (DLA group)',
        '49' => 'Maintenance and Repair Shop Equipment (DLA group)',
        '39' => 'Materials Handling Equipment (DLA group)',
        '51' => 'Hand Tools (DLA group)',
        '52' => 'Measuring Tools (DLA group)',
        '34' => 'Metalworking Machinery, incl. Welding (DLA group)',
        '80' => 'Brushes, Paints, Sealers and Adhesives (DLA group)',
        '81' => 'Containers, Packaging and Packing Supplies (DLA group)',
        '84' => 'Clothing, Individual Equipment and Insignia (DLA group)',
        '68' => 'Chemicals and Chemical Products (DLA group)',
        '79' => 'Cleaning Equipment and Supplies (DLA group)',
        '91' => 'Fuels, Lubricants, Oils and Waxes (DLA group)',
        '65' => 'Medical, Dental and Veterinary Equipment and Supplies (DLA group)',
        '56' => 'Construction and Building Materials (DLA group)',
        '43' => 'Pumps and Compressors (DLA group, optional)',
        '25' => 'Vehicular Equipment Components (DLA group, optional)',
        '5340' => 'Hardware, Commercial',
        '5305' => 'Screws',
        '5310' => 'Nuts and Washers',
        '5325' => 'Fastening Devices',
        '5330' => 'Packing and Gasket Materials',
        '5350' => 'Abrasive Materials',
        '5110' => 'Hand Tools, Edged, Nonpowered',
        '5120' => 'Hand Tools, Nonedged, Nonpowered',
        '5130' => 'Hand Tools, Power Driven',
        '5180' => 'Sets, Kits and Outfits of Hand Tools',
        '5975' => 'Electrical Hardware and Supplies',
        '5925' => 'Circuit Breakers',
        '5930' => 'Switches',
        '5945' => 'Relays and Solenoids',
        '5999' => 'Miscellaneous Electrical and Electronic Components',
        '4240' => 'Safety and Rescue Equipment',
        '8465' => 'Individual Equipment',
        '3010' => 'Torque Converters and Speed Changers',
        '3020' => 'Gears, Pulleys, Sprockets and Transmission Chain',
        '4460' => 'Air Purification Equipment',
        '4520' => 'Space and Water Heating Equipment',
        '4130' => 'Refrigeration and Air Conditioning Components',
        '6850' => 'Miscellaneous Chemical Specialties',
        '7930' => 'Cleaning and Polishing Compounds and Preparations',
        '5140' => 'Tool and Hardware Boxes',
        '9150' => 'Oils and Greases: Cutting, Lubricating and Hydraulic',
        '8145' => 'Specialized Shipping and Storage Containers',
    ],

    // Safety cap on NAICS + PSC terms per fetch (~1 s each; SAM.gov's daily
    // quota is unpublished). PSC terms beyond the cap are dropped with a warning.
    'max_terms_per_fetch' => (int) env('SAM_MAX_TERMS_PER_FETCH', 150),

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

    /*
    |--------------------------------------------------------------------------
    | Shared State Directory
    |--------------------------------------------------------------------------
    |
    | Where the latest fetch result (sam-opportunities.json, read by the control
    | panel and Excel export) and the compliance report are written. Tests point
    | this at a temporary directory so they never overwrite real results.
    |
    */

    'state_directory' => env('SAM_STATE_DIRECTORY', app_path('Mcp/Servers/Business/State')),
];
