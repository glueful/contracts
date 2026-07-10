<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Tenancy;

use RuntimeException;

/**
 * Thrown when a tenant-scoped query runs in tenant mode with no resolved tenant ('' from the
 * resolver). Fail-closed: never scope or stamp the '' partition (CurrentTenantResolver contract).
 */
final class TenantContextRequiredException extends RuntimeException
{
}
