<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Payments;

/**
 * What is being paid for.
 *
 * Matches the polymorphic payable_type/payable_id convention. Amount is always
 * integer minor units in the given currency.
 */
final class PayableReference
{
    /** @param array<string,mixed> $metadata */
    public function __construct(
        public readonly string $type,
        public readonly string $id,
        public readonly int $amount,
        public readonly string $currency,
        public readonly ?string $description = null,
        public readonly array $metadata = [],
    ) {
    }
}
