<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Payments;

/**
 * Provider-sourced readiness of a payout destination.
 *
 * Never operator-asserted: state is always derived from
 * PayoutCollector::inspectDestination().
 */
final class DestinationStatus
{
    public const PENDING = 'pending';
    public const READY = 'ready';
    public const RESTRICTED = 'restricted';

    private const STATES = [
        self::PENDING,
        self::READY,
        self::RESTRICTED,
    ];

    public function __construct(
        public readonly string $state,
        public readonly ?string $failureCode = null,
    ) {
        if (!in_array($state, self::STATES, true)) {
            throw new \InvalidArgumentException("Unknown DestinationStatus state '{$state}'.");
        }
    }
}
