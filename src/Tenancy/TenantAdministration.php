<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Tenancy;

use Glueful\Bootstrap\ApplicationContext;

/**
 * Tenant lifecycle and membership administration.
 *
 * The implementation owns the invariants: create() lands in provisioning;
 * markActive() accepts provisioning only; reactivate() accepts suspended only;
 * roles validate against the configured allowlist; and the final active owner
 * cannot be removed or demoted.
 */
interface TenantAdministration
{
    /** Creates a provisioning tenant and returns its uuid. */
    public function create(
        ApplicationContext $c,
        string $slug,
        string $name,
        string $ownerUserUuid
    ): string;

    public function suspend(ApplicationContext $c, string $tenantUuid): void;

    public function reactivate(ApplicationContext $c, string $tenantUuid): void;

    /** Seed-success boundary: provisioning to active. */
    public function markActive(ApplicationContext $c, string $tenantUuid): void;

    /** @return list<array{uuid:string,slug:string,name:string,status:string}> */
    public function listTenants(ApplicationContext $c, ?string $status = null): array;

    /** @return array{uuid:string,slug:string,name:string,status:string}|null */
    public function getTenant(ApplicationContext $c, string $tenantUuid): ?array;

    /**
     * Active memberships joined to active tenants for one user.
     *
     * @return list<array{uuid:string,slug:string,name:string,status:string}>
     */
    public function listTenantsForUser(ApplicationContext $c, string $userUuid): array;

    /** @return list<array{uuid:string,user_uuid:string,role:string,status:string}> */
    public function listMembers(ApplicationContext $c, string $tenantUuid): array;

    public function addMember(
        ApplicationContext $c,
        string $tenantUuid,
        string $userUuid,
        string $role
    ): void;

    public function removeMember(ApplicationContext $c, string $tenantUuid, string $userUuid): void;

    public function setMemberRole(
        ApplicationContext $c,
        string $tenantUuid,
        string $userUuid,
        string $role
    ): void;
}
