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

## Payments

`PaymentCollector` starts a payment for a `PayableReference` and must be
idempotent per `(type, id)`: repeated calls return or refresh the same logical
intent.

`PaymentConfirmationHandler` is the provider-to-owner seam. Payment providers
dispatch verified successful payments only, with `PaymentConfirmation::amount`
in integer minor units. The payable owner still compares amount and currency
against its own record before transitioning business state.
