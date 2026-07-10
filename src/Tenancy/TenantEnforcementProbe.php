<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Tenancy;

/** Read-side view of the active tenant-owned table registry. */
interface TenantEnforcementProbe
{
    public function isRegistered(string $table): bool;

    /** @return list<string> */
    public function registeredTables(): array;
}
