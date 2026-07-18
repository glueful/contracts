<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Tests\Unit\Payments;

use Glueful\Extensions\Contracts\Payments\DestinationStatus;
use PHPUnit\Framework\TestCase;

final class DestinationStatusTest extends TestCase
{
    public function testEnumConstants(): void
    {
        self::assertSame('pending', DestinationStatus::PENDING);
        self::assertSame('ready', DestinationStatus::READY);
        self::assertSame('restricted', DestinationStatus::RESTRICTED);
    }

    public function testConstructionRoundTripsAllFields(): void
    {
        $status = new DestinationStatus(DestinationStatus::RESTRICTED, 'kyc_required');

        self::assertSame(DestinationStatus::RESTRICTED, $status->state);
        self::assertSame('kyc_required', $status->failureCode);
    }

    public function testFailureCodeDefaultsToNull(): void
    {
        $status = new DestinationStatus(DestinationStatus::READY);

        self::assertNull($status->failureCode);
    }

    /**
     * @dataProvider validStateProvider
     */
    public function testAcceptsEachValidState(string $state): void
    {
        $status = new DestinationStatus($state);

        self::assertSame($state, $status->state);
    }

    /**
     * @return list<array{0:string}>
     */
    public static function validStateProvider(): array
    {
        return [
            [DestinationStatus::PENDING],
            [DestinationStatus::READY],
            [DestinationStatus::RESTRICTED],
        ];
    }

    public function testRejectsUnknownState(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new DestinationStatus('unverified');
    }
}
