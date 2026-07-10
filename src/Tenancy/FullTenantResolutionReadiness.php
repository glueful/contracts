<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Tenancy;

use Glueful\Bootstrap\ApplicationContext;

/** Capability seam implemented when full domain/path/header tenant resolution is active. */
interface FullTenantResolutionReadiness
{
    public function isReady(ApplicationContext $context): bool;
}
