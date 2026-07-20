<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Payments;

/**
 * Where a payout is sent.
 *
 * accountRef is an opaque, provider-owned reference (e.g. a connected-account
 * id); consumers store no raw bank/KYC/PII details alongside it.
 */
final class PayoutDestination
{
    /** @param array<string,mixed> $metadata */
    public function __construct(
        public readonly string $provider,
        public readonly string $accountRef,
        public readonly array $metadata = [],
    ) {
    }
}
