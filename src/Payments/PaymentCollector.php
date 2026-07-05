<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Payments;

use Glueful\Bootstrap\ApplicationContext;

/**
 * Starts payment collection for a payable.
 *
 * Implementations must be idempotent per (type, id): repeat calls return or
 * refresh the same logical payment intent and never create a second charge.
 * Throwing signals initiation failure; the consumer owns resume semantics.
 */
interface PaymentCollector
{
    public function initiate(ApplicationContext $context, PayableReference $payable): PaymentInitiation;
}
