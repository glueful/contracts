<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Tenancy;

use Glueful\Bootstrap\ApplicationContext;

/**
 * Domain administration over the tenant_domains surface.
 *
 * Hosts are normalized and validated on every write. DNS verification state
 * and operator-controlled activation state remain independent. While full
 * resolution is active, implementations must preserve every required default
 * host's final active mapping.
 */
interface TenantDomainAdministration
{
    /** @return array{uuid:string,token:string} */
    public function addDomain(ApplicationContext $c, string $tenantUuid, string $host): array;

    /** Performs DNS verification and returns the resulting verification status. */
    public function verifyDomain(ApplicationContext $c, string $domainUuid): string;

    public function disableDomain(ApplicationContext $c, string $domainUuid): void;

    public function enableDomain(ApplicationContext $c, string $domainUuid): void;

    public function removeDomain(ApplicationContext $c, string $domainUuid): void;

    /** @return list<array{uuid:string,host:string,verification_status:string,status:string}> */
    public function listDomains(ApplicationContext $c, string $tenantUuid): array;

    /**
     * @return array{
     *   uuid:string,
     *   tenant_uuid:string,
     *   host:string,
     *   verification_status:string,
     *   status:string
     * }|null
     */
    public function getDomain(ApplicationContext $c, string $domainUuid): ?array;

    /** Adds an operator-controlled, pre-verified host and returns its domain uuid. */
    public function addPreverifiedDomain(
        ApplicationContext $c,
        string $tenantUuid,
        string $host
    ): string;
}
