# CHANGELOG

All notable changes to glueful/extension-contracts will be documented in this file.

The format is based on Keep a Changelog, and this project adheres to Semantic Versioning.

## [Unreleased]

## [1.5.1] - 2026-08-18

### Added
- Declares the schema-free Glueful manifest (`migrations: "none"`): the package carries an
  `extra.glueful` block, so the framework 1.79 schema-on-enable contract requires an explicit
  declaration. Metadata only; no code changes and no framework-floor change.


## [1.5.0] - 2026-07-20

The payout + dispute payment seams — the contract surface commerce settles seller payouts through
and ingests provider chargebacks (and their reversals) through, keeping commerce free of any
concrete payment-provider reference.

### Added
- **Payments** — `PayoutCollector` contract: `transfer()`, `status()`, and `inspectDestination()`.
  Providers (e.g. Payvia) bind it to execute a seller payout, poll a payout's status, and inspect a
  destination's readiness; consumers resolve it softly and fall back to manual payouts when unbound.
- **Payments** — the payout value objects: `PayoutRequest` (integer minor-unit `amount`, `currency`,
  required `idempotencyKey`, optional `reason`), `PayoutResult` and `PayoutStatusResult`
  (`paid|pending|retryable_failure|terminal_failure|unknown` status constants — `PayoutStatusResult`
  adds `reversed` with a non-negative reversed amount), `PayoutDestination` (`provider`, `accountRef`,
  optional `metadata`), and `DestinationStatus` (`pending|ready|restricted` readiness with an optional
  failure code).
- **Payments** — `ProviderChargebackEvent` (extends `BaseEvent`): the neutral event a payment provider
  emits when it observes a chargeback or its later reversal (`chargeback|reversal` kinds), carrying
  `tenantUuid`, `provider`, `providerEventId`, `paymentReference`, a `PayableReference`, integer
  `amount`, `currency`, optional `reasonCode`, `occurredAt`, and — for a reversal — `relatedEventId`.
  Validated on construction: non-empty identifiers, positive amount, currency matching the payable,
  and a required `relatedEventId` whenever the kind is `reversal`.

## [1.4.0] - 2026-07-16

The refund seam — the payments contract surface commerce's refund domain issues gateway
refunds through, keeping commerce free of any concrete payment-provider reference.

### Added
- **Payments** — `RefundCollector` contract:
  `refund(ApplicationContext $context, PayableReference $payable, RefundRequest $request): RefundResult`.
  Providers (e.g. Payvia, once it grows a refund surface) bind it; consumers resolve it
  softly and fall back to manual refunds when unbound.
- **Payments** — `RefundRequest` (integer minor-unit `amount`, `currency`,
  required `idempotencyKey`, optional `reason`) and `RefundResult`
  (`completed|pending|failed` status constants, optional `providerRef`/`failureReason`).

## [1.3.0] - 2026-07-11

Workspace-deletion, host-retention, and domain re-verification seams — everything a host needs to
close the tenant lifecycle loop: reversible two-phase deletion, a cooldown-aware domain release that
prevents host squatting, and background re-verification of custom domains, all through neutral
contracts.

### Added
- **Tenancy** — `TenantAdministration` lifecycle methods `deleteTenant()`, `restoreTenant()`,
  `beginPurge()`, and `purgeTenantRecord()` for reversible trash→purge deletion, plus
  `getTenantLifecycle()`, an include-deleted projection for restore, purge-status, and crash recovery.
  The `listTenants()`/`getTenant()` projections gain the lifecycle fields (`deleted_at`,
  `deleted_from_status`, `purge_after`).
- **Tenancy** — `TenantDomainAdministration::releaseDomain()` (cooldown-aware host release that
  `removeDomain()` delegates to), `overrideCooldownAndClaim()` (highest-trust atomic cooldown override
  + claim), and `reverifyDomain()` (background DNS re-verification of a token-bearing domain).
- **Tenancy** — `HostCooldownException`, a structured claim conflict exposing only `availableAfter`
  (never the prior owner), and `DomainReverificationResult`, the neutral outcome DTO for a single
  re-verification (`verified|mismatch|dns_error|stale|ineligible` plus transition + counters).

## [1.2.0] - 2026-07-10

The full-resolution + tenant-management seams: everything a host needs to admit tenant two —
lifecycle/membership administration, custom-domain administration with independent DNS
verification, an activation-time resolution probe, a neutral request-middleware delegate, and
a privileged provisioning-context runner for seeding tenants that normal tenant context
correctly refuses.

### Added
- **Tenancy** — `TenantProvisioningRunner`
  (`runAsProvisioningTenant(string $tenantUuid, callable $fn): mixed`), the privileged context
  runner used ONLY while materializing a `provisioning` tenant: the normal
  `TenantContextRunner` correctly rejects non-active tenants, so seeding starter content
  before activation needs this narrowly-scoped seam instead of a weakened check.
- **Tenancy** — `TenantAdministration`, the neutral tenant lifecycle and
  membership-management seam, including provisioning-to-active transitions and
  final-owner protection as implementation invariants.
- **Tenancy** — `TenantDomainAdministration`, the neutral custom-domain CRUD,
  verification, and pre-verified activation seam.
- **Tenancy** — `TenantResolutionProbe`, an activation-time probe through the
  real public resolver and tenant-validation pipeline.
- **Tenancy** — `TenantRequestMiddleware`, a neutral route-middleware delegate
  for hosts that must remain inert when the tenancy implementation is absent.

## [1.1.0] - 2026-07-10

The tenancy enablement seams: everything a host app needs to take tenancy from
"off" to "on" — readiness reporting, first-tenant provisioning, enforcement
probing, tenant-context execution — through interfaces only, so the enablement
state machine never imports the tenancy extension's concrete classes.

### Added
- **Tenancy** — `TenantRuntimeReadiness` (can the host resolve tenant-owned
  requests safely right now? `isReady()` + `mode()` reporting
  `none | bootstrap_default | full_resolution`; the composite the enablement
  flow gates on) and `FullTenantResolutionReadiness` (capability seam bound
  ONLY when full domain/path/header resolution is active — its presence is how
  the composite learns full resolution exists, so a later resolver swaps the
  mode without touching storage or the state machine).
- **Tenancy** — `TenantProvisioner` (`provisionDefault()` stands up the first
  tenant + active owner membership through a neutral seam, **idempotent by
  caller-supplied uuid** so a crash-then-retry reuses the same tenant;
  `hasAnyTenant()` detects pre-existing installs so the consumer can refuse).
- **Tenancy** — `TenantEnforcementProbe` (read-side view of the tenant-owned
  table registry: `isRegistered()` / `registeredTables()`; lets a finalization
  gate PROVE every owned table is actually registered in the serving process
  instead of trusting a scoped query that would silently succeed unregistered).
- **Tenancy** — `TenantContextRunner` (run a callable as a given tenant / as the
  system channel / for-each active tenant; the neutral seam behind seed, sync,
  and background workers).
- **Tenancy** — `TenantScope` (fail-closed helper resolving the current tenant
  uuid for raw-SQL consumers: `null` when tenancy is inactive, the uuid when on,
  throws `TenantContextRequiredException` when on-but-empty) and its
  `TenantContextRequiredException`. Builder paths are auto-scoped by the tenancy
  guard/hook; raw PDO bypasses both, so raw consumers use this to decide whether
  to append a `tenant_uuid` predicate.

## [1.0.0] - 2026-07-05

Initial release: the shared seam layer that lets Glueful extensions plug into
each other through interfaces instead of each other's class names. Interfaces,
readonly value objects, and constants ONLY — no providers, no config, no
migrations. Governing rule (documented in the README): only IMPLEMENTERS bind
shared contract ids; consumers soft-resolve (`has() ? get() : inline fallback`),
so boot order is never a correctness boundary.

### Added
- **Tenancy** — `CurrentTenantResolver` (active tenant uuid or `''`; fail-closed
  semantics for tenant-mode consumers documented on the contract) and
  `TenantTableRegistry` (register tenant-owned tables without writing into
  another extension's config).
- **Payments** — `PayableReference` / `PaymentInitiation` / `PaymentConfirmation`
  value objects (integer minor units throughout), `PaymentCollector`
  (idempotent per payable), and `PaymentConfirmationHandler` with its container
  tag (success-only dispatch; owners verify amount/currency before
  transitioning state).
- **Email** — `EmailTemplateDefinition` / `EmailTemplatePlaceholder` value
  objects (key grammar `[a-z0-9][a-z0-9._-]*` validated at construction;
  placeholder samples drive test-sends) and `EmailTemplateRegistry` with the
  owner-collision rule (same-owner re-registration replaces; a different owner
  claiming an existing key must throw).
