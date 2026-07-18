<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Tests\Unit\Payments;

use Glueful\Extensions\Contracts\Payments\PayoutCollector;
use Glueful\Extensions\Contracts\Payments\PayoutResult;
use PHPUnit\Framework\TestCase;

final class PayoutResultTest extends TestCase
{
    public function testEnumConstants(): void
    {
        self::assertSame('paid', PayoutResult::PAID);
        self::assertSame('pending', PayoutResult::PENDING);
        self::assertSame('retryable_failure', PayoutResult::RETRYABLE_FAILURE);
        self::assertSame('terminal_failure', PayoutResult::TERMINAL_FAILURE);
        self::assertSame('unknown', PayoutResult::UNKNOWN);
    }

    public function testConstructionRoundTripsAllFields(): void
    {
        $result = new PayoutResult(
            PayoutResult::TERMINAL_FAILURE,
            'prov-ref-1',
            'account_closed',
            'The destination account has been closed.'
        );

        self::assertSame(PayoutResult::TERMINAL_FAILURE, $result->status);
        self::assertSame('prov-ref-1', $result->providerRef);
        self::assertSame('account_closed', $result->failureCode);
        self::assertSame('The destination account has been closed.', $result->failureReason);
    }

    public function testOptionalFieldsDefaultToNull(): void
    {
        $result = new PayoutResult(PayoutResult::PENDING);

        self::assertNull($result->providerRef);
        self::assertNull($result->failureCode);
        self::assertNull($result->failureReason);
    }

    /**
     * @dataProvider validStatusProvider
     */
    public function testAcceptsEachValidStatus(string $status): void
    {
        $result = new PayoutResult($status);

        self::assertSame($status, $result->status);
    }

    /**
     * @return list<array{0:string}>
     */
    public static function validStatusProvider(): array
    {
        return [
            [PayoutResult::PAID],
            [PayoutResult::PENDING],
            [PayoutResult::RETRYABLE_FAILURE],
            [PayoutResult::TERMINAL_FAILURE],
            [PayoutResult::UNKNOWN],
        ];
    }

    public function testRejectsUnknownStatus(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new PayoutResult('settled');
    }

    public function testPayoutCollectorInterfaceHasExpectedMethods(): void
    {
        $interface = new \ReflectionClass(PayoutCollector::class);

        self::assertTrue($interface->isInterface());
        self::assertTrue($interface->hasMethod('transfer'));
        self::assertTrue($interface->hasMethod('status'));
        self::assertTrue($interface->hasMethod('inspectDestination'));

        $transfer = $interface->getMethod('transfer');
        self::assertSame(3, $transfer->getNumberOfParameters());
        self::assertSame(PayoutResult::class, (string) $transfer->getReturnType());

        $status = $interface->getMethod('status');
        self::assertSame(3, $status->getNumberOfParameters());
        self::assertSame(
            'Glueful\Extensions\Contracts\Payments\PayoutStatusResult',
            (string) $status->getReturnType()
        );

        $inspect = $interface->getMethod('inspectDestination');
        self::assertSame(2, $inspect->getNumberOfParameters());
        self::assertSame(
            'Glueful\Extensions\Contracts\Payments\DestinationStatus',
            (string) $inspect->getReturnType()
        );
    }
}
