<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Payments;

use Glueful\Bootstrap\ApplicationContext;

/**
 * The inverse direction: a payment provider notifies the payable's owner of a
 * verified successful payment.
 *
 * Implementers tag their service with CONTAINER_TAG. Providers dispatch to each
 * handler whose supports() matches. Providers dispatch success-only; owners
 * still verify amount/currency against their payable and decide business
 * meaning, including late or mismatched arrivals.
 */
interface PaymentConfirmationHandler
{
    public const CONTAINER_TAG = 'extension_contracts.payment_confirmation_handlers';

    public function supports(string $payableType): bool;

    public function confirmed(
        ApplicationContext $context,
        PayableReference $payable,
        PaymentConfirmation $confirmation
    ): void;
}
