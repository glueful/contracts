<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Tenancy;

use Glueful\Bootstrap\ApplicationContext;

/** Reports whether the host can resolve tenant-owned requests safely. */
interface TenantRuntimeReadiness
{
    public const MODE_NONE = 'none';
    public const MODE_BOOTSTRAP_DEFAULT = 'bootstrap_default';
    public const MODE_FULL_RESOLUTION = 'full_resolution';

    public function isReady(ApplicationContext $context): bool;

    /** @return self::MODE_* */
    public function mode(ApplicationContext $context): string;
}
