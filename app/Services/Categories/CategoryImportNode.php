<?php

namespace App\Services\Categories;

/**
 * A single resolved node in a category import plan. Immutable — the committer
 * builds a path -> id map as it persists nodes rather than mutating these.
 */
class CategoryImportNode
{
    public const CREATE = 'create';

    public const REUSE = 'reuse';

    public function __construct(
        public readonly string $path,          // normalized full path, e.g. "abrasives/sanding"
        public readonly string $name,          // display name (first-seen casing)
        public readonly ?string $parentPath,   // normalized parent path, or null for a root
        public readonly ?int $parentExistingId, // DB id of parent when the parent is not itself a CSV node
        public readonly string $action,        // self::CREATE | self::REUSE
        public readonly ?string $slug,         // slug to create (CREATE) or the existing slug (REUSE)
        public readonly ?string $description,
        public readonly ?string $image,
        public readonly ?int $existingId,      // set when action === REUSE
        public readonly ?int $rowNumber,       // source CSV row number
    ) {}

    public function isCreate(): bool
    {
        return $this->action === self::CREATE;
    }

    public function isReuse(): bool
    {
        return $this->action === self::REUSE;
    }
}
