<?php

namespace App\Reporting\Definition;

use App\Reporting\Formula\Node;
use App\Reporting\Formula\Parser;

final class CalculatedField
{
    private ?Node $ast = null;

    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $formula,
        public readonly ?string $format = null,
    ) {}

    /**
     * The parsed formula. Only call on validated definitions.
     */
    public function ast(): Node
    {
        return $this->ast ??= Parser::parse($this->formula);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data['key'], $data['label'] ?? $data['key'], $data['formula'], $data['format'] ?? null);
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return array_filter([
            'key' => $this->key,
            'label' => $this->label,
            'formula' => $this->formula,
            'format' => $this->format,
        ], fn ($value) => $value !== null);
    }
}
