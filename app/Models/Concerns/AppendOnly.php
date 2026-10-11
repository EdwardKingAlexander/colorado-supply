<?php

namespace App\Models\Concerns;

use LogicException;

/**
 * Evidence records are never edited or deleted; a newer record supersedes.
 */
trait AppendOnly
{
    public static function bootAppendOnly(): void
    {
        static::updating(fn () => throw new LogicException(class_basename(static::class).' records are append-only.'));
        static::deleting(fn () => throw new LogicException(class_basename(static::class).' records are append-only.'));
    }
}
