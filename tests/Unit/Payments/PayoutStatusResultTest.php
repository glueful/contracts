<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Tests\Unit\Payments;

use Glueful\Extensions\Contracts\Payments\PayoutStatusResult;
use PHPUnit\Framework\TestCase;

final class PayoutStatusResultTest extends TestCase
{
    public function testEnumConstants(): void
    {
        self::assertSame('paid', PayoutStatusResult::PAID);
        self::assertSame('pending', PayoutStatusResult::PENDING);
        self::assertSame('retryable_failure', PayoutStatusResult::RETRYABLE_FAILURE);
        self::assertSame('terminal_failure', PayoutStatusResult::TERMINAL_FAILURE);
        self::assertSame('unknown', PayoutStatusResult::UNKNOWN);
        self::assertSame('reversed', PayoutStatusResult::REVERSED);
    }

    public function testConstructionRoundTripsAllFields(): void
    {
        $result = new PayoutStatusResult(
            PayoutStatusResult::REVERSED,
            4999,
            'prov-ref-1',
            'fraud_hold',
            'Reversed by provider fraud review.'
        );

        self::assertSame(PayoutStatusResult::REVERSED, $result->status);
        self::assertSame(4999, $result->reversedAmount);
        self::assertSame('prov-ref-1', $result->providerRef);
        self::assertSame('fraud_hold', $result->failureCode);
        self::assertSame('Reversed by provider fraud review.', $result->failureReason);
    }

    public function testReversedAmountDefaultsToZero(): void
    {
        $result = new PayoutStatusResult(PayoutStatusResult::PAID);

        self::assertSame(0, $result->reversedAmount);
    }

    public function testRejectsUnknownStatus(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new PayoutStatusResult('settled');
    }

    public function testRejectsNegativeReversedAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new PayoutStatusResult(PayoutStatusResult::PAID, -1);
    }

    public function testReversedRequiresPositiveReversedAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new PayoutStatusResult(PayoutStatusResult::REVERSED, 0);
    }

    public function testReversedWithPositiveReversedAmountIsValid(): void
    {
        $result = new PayoutStatusResult(PayoutStatusResult::REVERSED, 4999);

        self::assertSame(PayoutStatusResult::REVERSED, $result->status);
        self::assertSame(4999, $result->reversedAmount);
    }

    public function testPaidWithPositiveReversedAmountIsValidPartialReversal(): void
    {
        $result = new PayoutStatusResult(PayoutStatusResult::PAID, 1500);

        self::assertSame(PayoutStatusResult::PAID, $result->status);
        self::assertSame(1500, $result->reversedAmount);
    }

    public function testZeroReversedAmountIsValidForNonReversedStatuses(): void
    {
        $result = new PayoutStatusResult(PayoutStatusResult::PENDING, 0);

        self::assertSame(0, $result->reversedAmount);
    }
}
