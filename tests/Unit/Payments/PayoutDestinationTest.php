<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Tests\Unit\Payments;

use Glueful\Extensions\Contracts\Payments\PayoutDestination;
use PHPUnit\Framework\TestCase;

final class PayoutDestinationTest extends TestCase
{
    public function testConstructionRoundTripsAllFields(): void
    {
        $destination = new PayoutDestination('payvia', 'acct_123', ['bank' => 'gtbank']);

        self::assertSame('payvia', $destination->provider);
        self::assertSame('acct_123', $destination->accountRef);
        self::assertSame(['bank' => 'gtbank'], $destination->metadata);
    }

    public function testMetadataDefaultsToEmptyArray(): void
    {
        $destination = new PayoutDestination('payvia', 'acct_123');

        self::assertSame([], $destination->metadata);
    }
}
