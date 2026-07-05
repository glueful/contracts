<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Payments;

/**
 * What a storefront hands the client to proceed with payment.
 *
 * Status is 'ok' for a live gateway flow or 'manual' when no automated
 * collector is available and the payload carries instructions.
 */
final class PaymentInitiation
{
    /** @param array<string,mixed> $payload */
    public function __construct(
        public readonly string $provider,
        public readonly string $status,
        public readonly array $payload = [],
    ) {
    }
}
