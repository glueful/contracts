<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Payments;

use Glueful\Bootstrap\ApplicationContext;

/**
 * Returns money for a previously collected payable.
 *
 * Implementations must be idempotent per RefundRequest::$idempotencyKey:
 * repeat calls report the same logical refund and never move money twice.
 * Throwing signals infrastructure failure (unknown outcome); business
 * failure is a RefundResult with status RefundResult::FAILED.
 */
interface RefundCollector
{
    public function refund(
        ApplicationContext $context,
        PayableReference $payable,
        RefundRequest $request
    ): RefundResult;
}
