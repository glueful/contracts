<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Payments;

final class RefundResult
{
    public const COMPLETED = 'completed';
    public const PENDING = 'pending';
    public const FAILED = 'failed';

    public function __construct(
        public readonly string $status,
        public readonly ?string $providerRef = null,
        public readonly ?string $failureReason = null,
    ) {
    }
}
