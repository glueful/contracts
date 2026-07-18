<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Tests\Unit\Payments;

use Glueful\Events\Contracts\BaseEvent;
use Glueful\Extensions\Contracts\Payments\PayableReference;
use Glueful\Extensions\Contracts\Payments\ProviderChargebackEvent;
use PHPUnit\Framework\TestCase;

final class ProviderChargebackEventTest extends TestCase
{
    private function payable(int $amount = 5000, string $currency = 'usd'): PayableReference
    {
        return new PayableReference('order', 'ord-1', $amount, $currency);
    }

    public function testKindConstants(): void
    {
        self::assertSame('chargeback', ProviderChargebackEvent::KIND_CHARGEBACK);
        self::assertSame('reversal', ProviderChargebackEvent::KIND_REVERSAL);
    }

    public function testConstructsValidChargebackWithAllPropertiesReadable(): void
    {
        $payable = $this->payable();

        $event = new ProviderChargebackEvent(
            'tenant-1',
            'stripe',
            'evt_dispute_1',
            'pay_ref_1',
            $payable,
            5000,
            'usd',
            'fraudulent',
            '2026-07-18T10:00:00Z',
        );

        self::assertSame('tenant-1', $event->tenantUuid);
        self::assertSame('stripe', $event->provider);
        self::assertSame('evt_dispute_1', $event->providerEventId);
        self::assertSame('pay_ref_1', $event->paymentReference);
        self::assertSame($payable, $event->payable);
        self::assertSame(5000, $event->amount);
        self::assertSame('usd', $event->currency);
        self::assertSame('fraudulent', $event->reasonCode);
        self::assertSame('2026-07-18T10:00:00Z', $event->occurredAt);
        self::assertSame(ProviderChargebackEvent::KIND_CHARGEBACK, $event->kind);
        self::assertNull($event->relatedEventId);
        self::assertNotSame('', $event->getEventId());
        self::assertInstanceOf(BaseEvent::class, $event);
    }

    public function testKindDefaultsToChargeback(): void
    {
        $event = new ProviderChargebackEvent(
            'tenant-1',
            'stripe',
            'evt_dispute_1',
            'pay_ref_1',
            $this->payable(),
            5000,
            'usd',
            null,
            '2026-07-18T10:00:00Z',
        );

        self::assertSame(ProviderChargebackEvent::KIND_CHARGEBACK, $event->kind);
    }

    public function testConstructsValidReversalWithRelatedEventId(): void
    {
        $payable = $this->payable();

        $event = new ProviderChargebackEvent(
            'tenant-1',
            'stripe',
            'evt_reversal_1',
            'pay_ref_1',
            $payable,
            5000,
            'usd',
            null,
            '2026-07-18T12:00:00Z',
            ProviderChargebackEvent::KIND_REVERSAL,
            'evt_dispute_1',
        );

        self::assertSame(ProviderChargebackEvent::KIND_REVERSAL, $event->kind);
        self::assertSame('evt_dispute_1', $event->relatedEventId);
        self::assertInstanceOf(BaseEvent::class, $event);
        self::assertNotSame('', $event->getEventId());
    }

    public function testTenantUuidEmptyStringIsAccepted(): void
    {
        $event = new ProviderChargebackEvent(
            '',
            'stripe',
            'evt_dispute_1',
            'pay_ref_1',
            $this->payable(),
            5000,
            'usd',
            null,
            '2026-07-18T10:00:00Z',
        );

        self::assertSame('', $event->tenantUuid);
    }

    public function testRejectsEmptyProvider(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ProviderChargebackEvent(
            'tenant-1',
            '',
            'evt_dispute_1',
            'pay_ref_1',
            $this->payable(),
            5000,
            'usd',
            null,
            '2026-07-18T10:00:00Z',
        );
    }

    public function testRejectsWhitespaceOnlyProvider(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ProviderChargebackEvent(
            'tenant-1',
            '   ',
            'evt_dispute_1',
            'pay_ref_1',
            $this->payable(),
            5000,
            'usd',
            null,
            '2026-07-18T10:00:00Z',
        );
    }

    public function testRejectsEmptyProviderEventId(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ProviderChargebackEvent(
            'tenant-1',
            'stripe',
            '',
            'pay_ref_1',
            $this->payable(),
            5000,
            'usd',
            null,
            '2026-07-18T10:00:00Z',
        );
    }

    public function testRejectsEmptyPaymentReference(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ProviderChargebackEvent(
            'tenant-1',
            'stripe',
            'evt_dispute_1',
            '',
            $this->payable(),
            5000,
            'usd',
            null,
            '2026-07-18T10:00:00Z',
        );
    }

    public function testRejectsEmptyCurrency(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ProviderChargebackEvent(
            'tenant-1',
            'stripe',
            'evt_dispute_1',
            'pay_ref_1',
            $this->payable(),
            5000,
            '',
            null,
            '2026-07-18T10:00:00Z',
        );
    }

    public function testRejectsZeroAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ProviderChargebackEvent(
            'tenant-1',
            'stripe',
            'evt_dispute_1',
            'pay_ref_1',
            $this->payable(),
            0,
            'usd',
            null,
            '2026-07-18T10:00:00Z',
        );
    }

    public function testRejectsNegativeAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ProviderChargebackEvent(
            'tenant-1',
            'stripe',
            'evt_dispute_1',
            'pay_ref_1',
            $this->payable(),
            -1,
            'usd',
            null,
            '2026-07-18T10:00:00Z',
        );
    }

    public function testRejectsCurrencyMismatchWithPayable(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ProviderChargebackEvent(
            'tenant-1',
            'stripe',
            'evt_dispute_1',
            'pay_ref_1',
            $this->payable(5000, 'usd'),
            5000,
            'eur',
            null,
            '2026-07-18T10:00:00Z',
        );
    }

    public function testRejectsMalformedOccurredAt(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ProviderChargebackEvent(
            'tenant-1',
            'stripe',
            'evt_dispute_1',
            'pay_ref_1',
            $this->payable(),
            5000,
            'usd',
            null,
            'not-a-real-timestamp-!!!',
        );
    }

    public function testRejectsInvalidKind(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ProviderChargebackEvent(
            'tenant-1',
            'stripe',
            'evt_dispute_1',
            'pay_ref_1',
            $this->payable(),
            5000,
            'usd',
            null,
            '2026-07-18T10:00:00Z',
            'refund',
        );
    }

    public function testReversalRequiresRelatedEventId(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ProviderChargebackEvent(
            'tenant-1',
            'stripe',
            'evt_reversal_1',
            'pay_ref_1',
            $this->payable(),
            5000,
            'usd',
            null,
            '2026-07-18T10:00:00Z',
            ProviderChargebackEvent::KIND_REVERSAL,
            null,
        );
    }

    public function testReversalRejectsEmptyRelatedEventId(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ProviderChargebackEvent(
            'tenant-1',
            'stripe',
            'evt_reversal_1',
            'pay_ref_1',
            $this->payable(),
            5000,
            'usd',
            null,
            '2026-07-18T10:00:00Z',
            ProviderChargebackEvent::KIND_REVERSAL,
            '',
        );
    }
}
