<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Payments;

use Glueful\Events\Contracts\BaseEvent;

/**
 * Neutral inbound event: a payment provider reported a chargeback, or a
 * reversal of a previously reported chargeback, against a payment.
 *
 * Dispatched by an upstream payment-gateway integration (e.g. Payvia) once
 * it has resolved the disputed provider transaction to exactly one
 * persisted payment/tenant/payable — never constructed from raw webhook
 * metadata. Consumers (e.g. Commerce) ingest this event to reverse
 * settlement; this is an inbound event, not an outbound collector.
 *
 * tenantUuid MAY be the empty string in single-store mode — that is
 * explicitly allowed, not a validation failure.
 */
final class ProviderChargebackEvent extends BaseEvent
{
    public const KIND_CHARGEBACK = 'chargeback';
    public const KIND_REVERSAL = 'reversal';

    private const KINDS = [
        self::KIND_CHARGEBACK,
        self::KIND_REVERSAL,
    ];

    public function __construct(
        public readonly string $tenantUuid,
        public readonly string $provider,
        public readonly string $providerEventId,
        public readonly string $paymentReference,
        public readonly PayableReference $payable,
        public readonly int $amount,
        public readonly string $currency,
        public readonly ?string $reasonCode,
        public readonly string $occurredAt,
        public readonly string $kind = self::KIND_CHARGEBACK,
        public readonly ?string $relatedEventId = null,
    ) {
        parent::__construct();

        if (trim($provider) === '') {
            throw new \InvalidArgumentException('provider must not be empty.');
        }

        if (trim($providerEventId) === '') {
            throw new \InvalidArgumentException('providerEventId must not be empty.');
        }

        if (trim($paymentReference) === '') {
            throw new \InvalidArgumentException('paymentReference must not be empty.');
        }

        if (trim($currency) === '') {
            throw new \InvalidArgumentException('currency must not be empty.');
        }

        if ($amount <= 0) {
            throw new \InvalidArgumentException('amount must be greater than zero.');
        }

        if ($currency !== $payable->currency) {
            throw new \InvalidArgumentException('currency must match the payable currency.');
        }

        if (!self::isParseableTimestamp($occurredAt)) {
            throw new \InvalidArgumentException("occurredAt '{$occurredAt}' is not a parseable timestamp.");
        }

        if (!in_array($kind, self::KINDS, true)) {
            throw new \InvalidArgumentException("Unknown ProviderChargebackEvent kind '{$kind}'.");
        }

        if ($kind === self::KIND_REVERSAL && ($relatedEventId === null || trim($relatedEventId) === '')) {
            throw new \InvalidArgumentException('A reversal requires a non-empty relatedEventId.');
        }
    }

    private static function isParseableTimestamp(string $occurredAt): bool
    {
        if (strtotime($occurredAt) !== false) {
            return true;
        }

        try {
            new \DateTimeImmutable($occurredAt);

            return true;
        } catch (\Exception) {
            return false;
        }
    }
}
