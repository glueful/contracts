<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Payments;

/** Amount is integer minor units in $currency. */
final class RefundRequest
{
    public function __construct(
        public readonly int $amount,
        public readonly string $currency,
        public readonly string $idempotencyKey,
        public readonly ?string $reason = null,
    ) {
    }
}
