<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Tenancy;

use Glueful\Bootstrap\ApplicationContext;

/**
 * Resolves one host through the real public resolver and validation pipeline.
 *
 * This activation-only seam bypasses the deployment activation gate, not host
 * normalization, resolver precedence, tenant existence, or active-status checks.
 */
interface TenantResolutionProbe
{
    /** Returns the resolved tenant uuid, or null when the host does not resolve. */
    public function probePublicHost(ApplicationContext $c, string $host): ?string;
}
