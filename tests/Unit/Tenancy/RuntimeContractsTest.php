<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Tests\Unit\Tenancy;

use Glueful\Extensions\Contracts\Tenancy\FullTenantResolutionReadiness;
use Glueful\Extensions\Contracts\Tenancy\TenantEnforcementProbe;
use Glueful\Extensions\Contracts\Tenancy\TenantRuntimeReadiness;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class RuntimeContractsTest extends TestCase
{
    public function testRuntimeReadinessExposesStableModesAndMethods(): void
    {
        self::assertSame('none', TenantRuntimeReadiness::MODE_NONE);
        self::assertSame('bootstrap_default', TenantRuntimeReadiness::MODE_BOOTSTRAP_DEFAULT);
        self::assertSame('full_resolution', TenantRuntimeReadiness::MODE_FULL_RESOLUTION);

        $contract = new ReflectionClass(TenantRuntimeReadiness::class);
        self::assertTrue($contract->hasMethod('isReady'));
        self::assertTrue($contract->hasMethod('mode'));
    }

    public function testFullResolutionCapabilityHasReadinessMethod(): void
    {
        self::assertTrue((new ReflectionClass(FullTenantResolutionReadiness::class))->hasMethod('isReady'));
    }

    public function testEnforcementProbeExposesMembershipAndInventory(): void
    {
        $contract = new ReflectionClass(TenantEnforcementProbe::class);
        self::assertTrue($contract->hasMethod('isRegistered'));
        self::assertTrue($contract->hasMethod('registeredTables'));
    }
}
