<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Payments;

use Glueful\Bootstrap\ApplicationContext;

/**
 * Provider-neutral payout/transfer port.
 *
 * transfer() and status() are idempotent per idempotencyKey: repeat calls
 * about the same attempt report the same logical outcome and never move
 * money twice. Throwing signals infrastructure failure or an unknown
 * outcome — the consumer must reconcile via status(), never blindly retry.
 * A returned result is always a classified outcome; PayoutResult::UNKNOWN /
 * PayoutStatusResult::UNKNOWN remain explicitly ambiguous and require
 * reconciliation before any further action.
 */
interface PayoutCollector
{
    /**
     * Move money to a destination. Idempotent per $request->idempotencyKey:
     * repeat calls with the same key report the same logical transfer.
     */
    public function transfer(
        ApplicationContext $context,
        PayoutDestination $destination,
        PayoutRequest $request
    ): PayoutResult;

    /**
     * Reconcile the current provider-side state of the transfer identified
     * by $idempotencyKey. Idempotent: safe to call repeatedly for the same
     * key.
     */
    public function status(
        ApplicationContext $context,
        PayoutDestination $destination,
        string $idempotencyKey
    ): PayoutStatusResult;

    /**
     * Provider-sourced readiness of a payout destination. Never
     * operator-asserted.
     */
    public function inspectDestination(
        ApplicationContext $context,
        PayoutDestination $destination
    ): DestinationStatus;
}
