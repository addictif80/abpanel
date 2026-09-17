<?php

namespace App\Models\Concerns;

use Illuminate\Database\QueryException;

/**
 * generateNumber() reads the last assigned number and adds 1 with no
 * database lock. Two near-simultaneous creations (e.g. two admins clicking
 * "create" at the same moment) can compute the same number; the unique
 * constraint on `number` then makes the second insert throw instead of
 * silently duplicating. createWithUniqueNumber() retries a few times with a
 * freshly generated number when that happens, instead of surfacing a 500.
 */
trait HasRetryableNumber
{
    public static function createWithUniqueNumber(array $attributes, int $attempts = 3): static
    {
        for ($i = 1; $i <= $attempts; $i++) {
            try {
                return static::create($attributes + ['number' => static::generateNumber()]);
            } catch (QueryException $e) {
                $isNumberCollision = str_contains($e->getMessage(), 'number');
                if ($i === $attempts || !$isNumberCollision) {
                    throw $e;
                }
            }
        }
    }
}
