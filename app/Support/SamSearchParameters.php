<?php

namespace App\Support;

class SamSearchParameters
{
    public array $naics_codes_primary;

    public array $naics_codes_secondary;

    public array $naics_descriptions;

    public array $psc_codes;

    public array $keywords;

    public array $notice_types;

    public array $set_asides;

    public string $place_of_performance;

    public array $search_logic_order;

    public string $search_logic_sort_by;

    public string $search_logic_fallback;

    public array $output_requirements;

    public string $error_handling_incomplete_entries;

    public array $error_handling_flag_missing;

    public bool $error_handling_highlight_incomplete;

    public string $refresh_interval_default;

    public array $refresh_interval_options;

    public function __construct()
    {
        $config = config('sam_opportunities');

        $this->naics_codes_primary = $config['naics_codes']['primary'];
        $this->naics_codes_secondary = $config['naics_codes']['secondary'];
        $this->naics_descriptions = $config['naics_descriptions'] ?? [];
        $this->psc_codes = $config['psc_codes'];
        $this->keywords = $config['keywords'];
        $this->notice_types = $config['filters']['notice_types'];
        $this->set_asides = $config['filters']['set_asides'];
        $this->place_of_performance = $config['filters']['place_of_performance'];
        $this->search_logic_order = $config['search_logic']['order'];
        $this->search_logic_sort_by = $config['search_logic']['sort_by'];
        $this->search_logic_fallback = $config['search_logic']['fallback'];
        $this->output_requirements = $config['output_requirements'];
        $this->error_handling_incomplete_entries = $config['error_handling']['incomplete_entries'];
        $this->error_handling_flag_missing = $config['error_handling']['flag_missing'];
        $this->error_handling_highlight_incomplete = $config['error_handling']['highlight_incomplete'];
        $this->refresh_interval_default = $config['refresh_interval']['default'];
        $this->refresh_interval_options = $config['refresh_interval']['options'];
    }
}
