<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Tenancy;

use Glueful\Bootstrap\ApplicationContext;

/**
 * Provisions the default tenant (and its owner membership) through a neutral seam.
 *
 * The retrofit/enablement path in a consumer app uses this contract to stand up its first
 * tenant WITHOUT importing the tenancy extension's concrete Tenant/TenantMembership models.
 * Implementations are bound by the tenancy extension; consumers depend ONLY on this interface.
 *
 * Contract:
 *  - provisionDefault(): create the tenant with the GIVEN uuid/slug/name and an active owner
 *    membership for $ownerUserUuid, then return the tenant uuid. IDEMPOTENT BY UUID — calling
 *    it twice with the same $tenantUuid is a no-op that returns that uuid (never a duplicate).
 *    The uuid is caller-supplied (not auto-generated) so a crash-then-retry reuses the same one.
 *  - hasAnyTenant(): whether any tenant row exists at all (used to detect a pre-existing install
 *    before provisioning — the consumer decides whether to block).
 */
interface TenantProvisioner
{
    public function provisionDefault(
        ApplicationContext $context,
        string $tenantUuid,
        string $slug,
        string $name,
        string $ownerUserUuid
    ): string;

    public function hasAnyTenant(ApplicationContext $context): bool;
}
