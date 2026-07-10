<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Tests\Unit\Tenancy;

use Glueful\Extensions\Contracts\Tenancy\TenantAdministration;
use Glueful\Extensions\Contracts\Tenancy\TenantDomainAdministration;
use Glueful\Extensions\Contracts\Tenancy\TenantResolutionProbe;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;

final class AdministrationContractsTest extends TestCase
{
    public function testTenantAdministrationExposesStableResultTypes(): void
    {
        $contract = new ReflectionClass(TenantAdministration::class);

        self::assertTrue($contract->isInterface());
        self::assertReturnType($contract, 'create', 'string', false);
        self::assertReturnType($contract, 'getTenant', 'array', true);
    }

    public function testDomainAdministrationExposesStableResultTypes(): void
    {
        $contract = new ReflectionClass(TenantDomainAdministration::class);

        self::assertTrue($contract->isInterface());
        self::assertReturnType($contract, 'addDomain', 'array', false);
        self::assertReturnType($contract, 'getDomain', 'array', true);
    }

    public function testResolutionProbeMayReturnNoTenant(): void
    {
        $contract = new ReflectionClass(TenantResolutionProbe::class);

        self::assertTrue($contract->isInterface());
        self::assertReturnType($contract, 'probePublicHost', 'string', true);
    }

    private static function assertReturnType(
        ReflectionClass $contract,
        string $method,
        string $name,
        bool $nullable
    ): void {
        $type = $contract->getMethod($method)->getReturnType();

        self::assertInstanceOf(ReflectionNamedType::class, $type);
        self::assertSame($name, $type->getName());
        self::assertSame($nullable, $type->allowsNull());
    }
}
