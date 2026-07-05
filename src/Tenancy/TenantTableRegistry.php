<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Tenancy;

/**
 * Registration channel for tenant-owned tables.
 *
 * Implementing tenancy packages feed these table names into their authoritative
 * raw-query backstop registry. Re-registering a table must be a no-op.
 */
interface TenantTableRegistry
{
    /** @param list<string> $tables */
    public function register(array $tables): void;
}
