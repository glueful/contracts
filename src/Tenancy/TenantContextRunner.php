<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Tenancy;

/**
 * Runs a callable inside an explicit tenant / system context.
 *
 * The neutral seam a no-ORM consumer uses to establish tenant scoping around work that must
 * be stamped and read-scoped (seeders, sync, background jobs, CLI). Implementations are
 * bound by the tenancy extension; consumers depend ONLY on this interface, never on the
 * extension's concrete context classes.
 *
 * Contract:
 *  - runAsTenant(): $fn runs with $tenantUuid as the current tenant; reads are scoped and
 *    writes are stamped to it. Returns whatever $fn returns.
 *  - runAsSystem(): $fn runs in an explicit bypass context — no tenant scoping. For trusted
 *    cross-tenant / infrastructure work (retrofit, enablement). Returns whatever $fn returns.
 *  - forEachTenant(): invokes $fn once per active tenant, each inside that tenant's context.
 *    Iteration is DETERMINISTICALLY ordered (creation date, then name, then uuid) and
 *    FAIL-FAST: on the first failure it stops and surfaces the offending tenant uuid. A
 *    "continue on error" mode is a caller/CLI concern, never this contract's default.
 */
interface TenantContextRunner
{
    public function runAsTenant(string $tenantUuid, callable $fn): mixed;

    public function runAsSystem(callable $fn): mixed;

    /** @param callable(string $tenantUuid): void $fn */
    public function forEachTenant(callable $fn): void;
}
