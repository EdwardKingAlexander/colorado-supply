<?php

namespace App\Reporting\Definition;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * Date filter values: 'Y-m-d' (a whole day) or 'Y-m-d H:i[:s]' (an instant).
 */
final class DateValue
{
    private function __construct(
        public readonly CarbonImmutable $date,
        public readonly bool $hasTime,
    ) {}

    public static function parse(mixed $value): ?self
    {
        if (! is_string($value)) {
            return null;
        }

        foreach (['!Y-m-d' => false, '!Y-m-d H:i:s' => true, '!Y-m-d H:i' => true] as $format => $hasTime) {
            try {
                $date = CarbonImmutable::createFromFormat($format, $value);
            } catch (InvalidArgumentException) {
                continue;
            }

            if ($date instanceof CarbonImmutable && $date->format(substr($format, 1)) === $value) {
                return new self($date, $hasTime);
            }
        }

        return null;
    }

    public function start(): CarbonImmutable
    {
        return $this->hasTime ? $this->date : $this->date->startOfDay();
    }

    public function end(): CarbonImmutable
    {
        return $this->hasTime ? $this->date : $this->date->endOfDay();
    }
}
