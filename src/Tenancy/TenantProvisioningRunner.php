<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Tenancy;

/** Privileged context runner used only while materializing a provisioning tenant. */
interface TenantProvisioningRunner
{
    public function runAsProvisioningTenant(string $tenantUuid, callable $fn): mixed;
}
