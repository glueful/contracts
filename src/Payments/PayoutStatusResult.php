<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Payments;

/**
 * Outcome of a PayoutCollector::status() reconcile call.
 *
 * Carries the same vocabulary as PayoutResult plus REVERSED (a transfer that
 * settled then was fully reversed by the provider). reversedAmount is
 * cumulative; a positive reversedAmount with status PAID is a valid partial
 * reversal. This value object has no amount context of its own — the
 * consumer asserts reversedAmount against the original payout amount to
 * decide full-vs-partial reversal.
 */
final class PayoutStatusResult
{
    public const PAID = 'paid';
    public const PENDING = 'pending';
    public const RETRYABLE_FAILURE = 'retryable_failure';
    public const TERMINAL_FAILURE = 'terminal_failure';
    public const UNKNOWN = 'unknown';
    public const REVERSED = 'reversed';

    private const STATUSES = [
        self::PAID,
        self::PENDING,
        self::RETRYABLE_FAILURE,
        self::TERMINAL_FAILURE,
        self::UNKNOWN,
        self::REVERSED,
    ];

    public function __construct(
        public readonly string $status,
        public readonly int $reversedAmount = 0,
        public readonly ?string $providerRef = null,
        public readonly ?string $failureCode = null,
        public readonly ?string $failureReason = null,
    ) {
        if (!in_array($status, self::STATUSES, true)) {
            throw new \InvalidArgumentException("Unknown PayoutStatusResult status '{$status}'.");
        }

        if ($reversedAmount < 0) {
            throw new \InvalidArgumentException('reversedAmount must not be negative.');
        }

        if ($status === self::REVERSED && $reversedAmount <= 0) {
            throw new \InvalidArgumentException('REVERSED requires a positive reversedAmount.');
        }
    }
}
