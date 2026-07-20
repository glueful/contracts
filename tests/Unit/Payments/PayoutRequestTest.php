<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Tests\Unit\Payments;

use Glueful\Extensions\Contracts\Payments\PayoutRequest;
use PHPUnit\Framework\TestCase;

final class PayoutRequestTest extends TestCase
{
    public function testConstructionRoundTripsAllFields(): void
    {
        $request = new PayoutRequest(4999, 'USD', 'payout-1:attempt:1', 'weekly settlement');

        self::assertSame(4999, $request->amount);
        self::assertSame('USD', $request->currency);
        self::assertSame('payout-1:attempt:1', $request->idempotencyKey);
        self::assertSame('weekly settlement', $request->reason);
    }

    public function testReasonDefaultsToNull(): void
    {
        $request = new PayoutRequest(4999, 'USD', 'payout-1:attempt:1');

        self::assertNull($request->reason);
    }
}
