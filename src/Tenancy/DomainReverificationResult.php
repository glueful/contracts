<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Tenancy;

/** Neutral outcome of a single custom-domain ownership re-check. */
final class DomainReverificationResult
{
    /**
     * @param 'verified'|'mismatch'|'dns_error'|'stale'|'ineligible' $outcome
     * @param 'none'|'revoked'|'restored' $transition
     */
    public function __construct(
        public readonly string $outcome,
        public readonly ?string $verificationStatus,
        public readonly string $transition,
        public readonly int $consecutiveFailures,
        public readonly ?string $checkedAt,
    ) {
    }
}
