<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Tests\Unit\Tenancy;

use Glueful\Extensions\Contracts\Tenancy\HostCooldownException;
use PHPUnit\Framework\TestCase;

final class HostCooldownExceptionTest extends TestCase
{
    public function testCarriesAvailabilityWithoutPriorOwnerIdentity(): void
    {
        $exception = new HostCooldownException('2026-08-10 12:00:00');

        self::assertSame('2026-08-10 12:00:00', $exception->availableAfter());
        self::assertStringNotContainsString('tenant', $exception->getMessage());
    }
}
