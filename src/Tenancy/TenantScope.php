<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Tenancy;

use Glueful\Bootstrap\ApplicationContext;

/**
 * Fail-closed tenant-scope resolution for raw-SQL consumers. Builder paths are auto-scoped by the
 * tenancy read guard/insert hook; raw PDO is not, so raw consumers call this to decide whether to
 * append a tenant_uuid predicate.
 *
 * null  → tenancy inactive (resolver unbound → autowired null): emit pre-tenancy SQL unchanged.
 * uuid  → scoping on.
 * throw → on-but-empty (fail-closed).
 *
 * $context is nullable only so consumers can accept a nullable ApplicationContext for direct
 * (non-container) construction; whenever $resolver is non-null a real context is required.
 */
final class TenantScope
{
    public static function current(?CurrentTenantResolver $resolver, ?ApplicationContext $context): ?string
    {
        if ($resolver === null || $context === null) {
            return null;
        }
        $uuid = $resolver->tenantUuid($context);
        if ($uuid === '') {
            throw new TenantContextRequiredException(
                'A tenant-scoped raw query ran with no resolved tenant (fail-closed).',
            );
        }
        return $uuid;
    }
}
