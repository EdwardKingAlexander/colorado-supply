<?php

namespace App\Services\PubLog;

class PubLogSchema
{
    /** Members that are imported. The status/replacement history is optional (phase 5). */
    public static function members(): array
    {
        return array_diff_key(self::allMembers(), config('nsn_lookup.import_relationships') ? [] : array_flip(self::RELATIONSHIP_MEMBERS));
    }

    public const RELATIONSHIP_MEMBERS = ['V_FLIS_STANDARDIZATION.CSV', 'V_FLIS_CANCELLED_NIIN.CSV'];

    /** October 2026 verified source contract. Older incompatible schemas fail closed. */
    public static function allMembers(): array
    {
        return [
            'P_FLIS_NSN.CSV' => ['archive' => 'IDENTIFICATION', 'table' => 'nsn_catalog_items', 'encoding' => 'UTF-8', 'fields' => ['FSC', 'NIIN', 'INC', 'ITEM_NAME', 'SOS', 'END_ITEM_NAME', 'CANCELLED_NIIN']],
            'P_CAGE.CSV' => ['archive' => 'CAGE', 'table' => 'cage_entities', 'encoding' => 'UTF-8', 'fields' => ['CAGE_CODE', 'CAGE_STATUS', 'COMPANY', 'CITY', 'STATE_PROVINCE', 'ZIP_POSTAL_ZONE', 'COUNTRY']],
            'V_CAGE_ADDRESS.CSV' => ['archive' => 'CAGE', 'table' => 'cage_entities', 'encoding' => 'UTF-8', 'fields' => ['CAGE_CODE', 'COMPANY_NAME', 'STREET_ADDRESS_1', 'STREET_ADDRESS_2', 'STREET_ADDRESS_3', 'CITY', 'STATE', 'ZIP', 'COUNTRY']],
            'V_FLIS_PART.CSV' => ['archive' => 'REFERENCE', 'table' => 'nsn_references', 'encoding' => 'UTF-8', 'fields' => ['NIIN', 'PART_NUMBER', 'CAGE_CODE', 'CAGE_STATUS', 'RNCC', 'RNVC', 'DAC', 'RNAAC', 'RNFC', 'RNSC', 'RNJC', 'SADC', 'HCC', 'MSDS', 'MEDALS']],
            'V_FLIS_STANDARDIZATION.CSV' => ['archive' => 'IDENTIFICATION', 'table' => 'nsn_catalog_relationships', 'encoding' => 'UTF-8', 'fields' => ['NIIN', 'RELATED_NSN', 'ISC', 'ORIG_STDZN_DEC', 'DT_STDZN_DEC', 'NIIN_STAT_CD']],
            'V_FLIS_CANCELLED_NIIN.CSV' => ['archive' => 'IDENTIFICATION', 'table' => 'nsn_catalog_relationships', 'encoding' => 'UTF-8', 'fields' => ['NIIN', 'CANCELLED_NIIN_FSC', 'CANCELLED_NIIN', 'NIIN_STAT_CD', 'EFF_DATE', 'DEMIL']],
            'P_HELP.CSV' => ['archive' => 'IDENTIFICATION', 'table' => 'pub_log_definitions', 'encoding' => 'Windows-1252', 'fields' => ['CODE', 'LITERAL', 'DESCRIPTION', 'REGS']],
        ];
    }

    public static function optionalMembers(): array
    {
        return ['V_FLIS_IDENTIFICATION.CSV', 'V_COLLOQUIAL_NAME.CSV'];
    }
}
