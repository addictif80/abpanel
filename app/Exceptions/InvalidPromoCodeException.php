<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown specifically for a rejected promo code, so callers can catch this
 * (and only this) to show a "code promo invalide" form error — a generic
 * \RuntimeException catch here would also swallow unrelated failures (e.g. a
 * QueryException from the resource insert, which extends RuntimeException)
 * and mislabel them as a promo code problem.
 */
class InvalidPromoCodeException extends RuntimeException
{
}
