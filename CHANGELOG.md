# CHANGELOG

All notable changes to glueful/extension-contracts will be documented in this file.

The format is based on Keep a Changelog, and this project adheres to Semantic Versioning.

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
