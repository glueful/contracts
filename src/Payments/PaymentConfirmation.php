<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Payments;

/**
 * Structured verified payment facts.
 *
 * Providers dispatch success-only, so status is expected to be 'paid'. The
 * field remains explicit so handlers can assert it. Amount is integer minor
 * units; provider integrations own conversion from gateway-specific payloads.
 */
final class PaymentConfirmation
{
    /** @param array<string,mixed> $providerPayload */
    public function __construct(
        public readonly string $status,
        public readonly string $reference,
        public readonly int $amount,
        public readonly string $currency,
        public readonly array $providerPayload = [],
    ) {
    }
}
