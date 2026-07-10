<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Tests\Unit\Tenancy;

use Glueful\Extensions\Contracts\Tenancy\TenantProvisioningRunner;
use PHPUnit\Framework\TestCase;

final class ProvisioningRunnerContractTest extends TestCase
{
    public function testContractCarriesCallbackResult(): void
    {
        $runner = new class implements TenantProvisioningRunner {
            public function runAsProvisioningTenant(string $tenantUuid, callable $fn): mixed
            {
                return $fn();
            }
        };

        self::assertSame('ok', $runner->runAsProvisioningTenant('tenant000001', static fn() => 'ok'));
    }
}
