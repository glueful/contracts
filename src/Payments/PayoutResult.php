<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Payments;

/**
 * Outcome of a PayoutCollector::transfer() call.
 *
 * A returned result is always a classified outcome; UNKNOWN is explicitly
 * ambiguous and must be resolved via PayoutCollector::status(), never
 * blindly retried.
 */
final class PayoutResult
{
    public const PAID = 'paid';
    public const PENDING = 'pending';
    public const RETRYABLE_FAILURE = 'retryable_failure';
    public const TERMINAL_FAILURE = 'terminal_failure';
    public const UNKNOWN = 'unknown';

    private const STATUSES = [
        self::PAID,
        self::PENDING,
        self::RETRYABLE_FAILURE,
        self::TERMINAL_FAILURE,
        self::UNKNOWN,
    ];

    public function __construct(
        public readonly string $status,
        public readonly ?string $providerRef = null,
        public readonly ?string $failureCode = null,
        public readonly ?string $failureReason = null,
    ) {
        if (!in_array($status, self::STATUSES, true)) {
            throw new \InvalidArgumentException("Unknown PayoutResult status '{$status}'.");
        }
    }
}
