# Extension Contracts

Shared cross-extension contracts for the Glueful ecosystem. This package is
intentionally small: interfaces, readonly value objects, and constants only.

## Soft Binding Rule

Only implementers bind shared contract IDs in the container. Consumers resolve a
contract with `has() ? get() : <inline fallback>` and never bind defaults under a
shared contract ID. That avoids boot-order and last-wins container surprises.

## Tenancy

`CurrentTenantResolver::tenantUuid()` returns the active tenant UUID or `''`.
The empty string is a valid single-store sentinel only when the consuming
extension is not running in tenant mode. In tenant mode, a bound resolver
returning `''` means missing tenant context and the consumer must fail closed
unless it is running an explicitly named system/maintenance path.

`TenantTableRegistry` lets tenant-aware extensions register tenant-owned tables
without writing into another extension's config.

`TenantRuntimeReadiness` reports whether the host can resolve tenant-owned
requests safely (`isReady()`) and how (`mode()`:
`none | bootstrap_default | full_resolution`). `FullTenantResolutionReadiness`
is a capability seam bound only when full domain/path/header resolution is
active — its container presence is how a readiness composite upgrades the mode.

`TenantProvisioner` stands up the first tenant + active owner membership
through a neutral seam. `provisionDefault()` is idempotent by caller-supplied
uuid (a crash-then-retry reuses the same tenant); `hasAnyTenant()` lets a
consumer refuse to provision over a pre-existing install.

`TenantEnforcementProbe` is the read-side view of the tenant-owned table
registry (`isRegistered()` / `registeredTables()`), so a finalization gate can
prove every owned table is registered in the serving process.

`TenantContextRunner` runs a callable as a given tenant, as the system channel,
or for each active tenant — the seam behind seed, sync, and background workers.

`TenantScope` resolves the current tenant uuid for raw-SQL consumers,
fail-closed: `null` when tenancy is inactive, the uuid when on, and
`TenantContextRequiredException` when on-but-empty. Builder paths are
auto-scoped by the tenancy guard/hook; raw PDO bypasses both, so raw consumers
use this to decide whether to append a `tenant_uuid` predicate.

## Payments

`PaymentCollector` starts a payment for a `PayableReference` and must be
idempotent per `(type, id)`: repeated calls return or refresh the same logical
intent.

`PaymentConfirmationHandler` is the provider-to-owner seam. Payment providers
dispatch verified successful payments only, with `PaymentConfirmation::amount`
in integer minor units. The payable owner still compares amount and currency
against its own record before transitioning business state.

## Email

`EmailTemplateRegistry` lets any extension declare mail templates as data —
`EmailTemplateDefinition` (key, label, default subject/body, owner) with
`EmailTemplatePlaceholder` metadata (name, description, sample) that drives
admin chips and test-sends. Implementations must enforce the collision rule:
re-registering a key is allowed only for the same owner; a different owner
claiming an existing key throws at boot. Registrant extensions soft-resolve
the registry (`has() ? register : skip`), so a missing email channel degrades
to "templates simply aren't registered".
