<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Tenancy;

use Glueful\Bootstrap\ApplicationContext;

/**
 * The active tenant, or '' when none is resolved. NEVER null: '' doubles as
 * the single-store sentinel scoping key in non-tenant mode.
 *
 * Fail-closed rule: a consumer running in tenant mode with this contract bound
 * must treat '' as "missing tenant context" and throw/403. It must never scope
 * or stamp the '' partition. Only explicitly named system/maintenance paths may
 * bypass that rule.
 */
interface CurrentTenantResolver
{
    public function tenantUuid(ApplicationContext $context): string;
}
