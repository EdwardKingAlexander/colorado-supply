<?php

namespace App\Enums;

enum DocumentType: string
{
    case License = 'license';
    case Insurance = 'insurance';
    case Registration = 'registration';
    case TaxDocument = 'tax_document';
    case Contract = 'contract';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::License => 'License',
            self::Insurance => 'Insurance',
            self::Registration => 'Registration',
            self::TaxDocument => 'Tax Document',
            self::Contract => 'Contract',
            self::Other => 'Other',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::License => 'info',
            self::Insurance => 'success',
            self::Registration => 'primary',
            self::TaxDocument => 'warning',
            self::Contract => 'gray',
            self::Other => 'gray',
        };
    }
}
