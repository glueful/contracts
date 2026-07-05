# Extension Contracts — Design

**Date:** 2026-07-05
**Status:** Draft for review
**Package:** `glueful/extension-contracts` — the shared seam layer that lets
Glueful extensions plug into each other through interfaces instead of each
other's class names.
**Prerequisite for:** `glueful/commerce` v1 (its plan is amended to consume
these contracts; see §6).

## Goal

Today extension composition means `class_exists(\Glueful\Extensions\Payvia\...)`
probes and `mergeConfig('tenancy', ...)` writes into another package's config —
extension-to-extension knowledge that gets brittle as the catalog grows. This
package is the identity-seam idea generalized: **implementers bind a contract,
consumers resolve a contract, and neither knows the other exists.** It is the
lemma-contracts form applied across extensions.

## 1. Package shape

- **Name/namespace:** `glueful/extension-contracts`, PSR-4
  `Glueful\Extensions\Contracts\` (the framework already owns
  `Glueful\Contracts\` — collision checked; `Glueful\Extensions\Contracts\`
  sits beside the per-extension `Glueful\Extensions\{Name}\` subtrees).
- **Contents:** interfaces, tiny readonly value objects, and shared constants
  ONLY. No service provider, no migrations, no config, no boot logic.
  **Dependency honesty (P2 pin):** the interfaces type-hint
  `Glueful\Bootstrap\ApplicationContext`, so the package takes a HARD
  `glueful/framework` require — every implementer and consumer already
  depends on it, and pretending otherwise would just hide a runtime type
  dependency. It is framework-DEPENDENT but logic-free.
- **Dependency direction:** implementers (tenancy, payvia) and consumers
  (commerce) both take a HARD composer require on this package — it is tiny
  and inert. What stays SOFT is the *implementation's presence at runtime*,
  probed via `container->has(Contract::class)`.
- **Versioning:** independent semver; contract changes never wait on a
  framework release. v1 scope is deliberately just enough for commerce.

## 2. The soft-binding precedence rule (load-bearing)

Extension-vs-extension container precedence is boot-order dependent, so:

> A CONSUMER never binds a default under a shared contract id. Consumers
> resolve via `$container->has(Contract::class) ? $container->get(...) :
> <own fallback constructed inline>` (the lemma soft-binding pattern). Only
> the IMPLEMENTING extension binds the contract id.

Concretely: commerce never binds `PaymentCollector::class` or
`CurrentTenantResolver::class` in the container — its factories fall back to
`new ManualPaymentCollector()` / `new SentinelTenantResolver()` when nothing
is bound. Payvia and tenancy are the only packages that bind those ids. No
ordering games, no last-wins surprises.

## 3. v1 contracts

### `Glueful\Extensions\Contracts\Tenancy\`

- **`CurrentTenantResolver`** — `tenantUuid(ApplicationContext $context): string`.
  Returns the active tenant uuid or `''` (no active tenant — never null).
  Tenancy's implementation reads its `TenantContext::currentTenant()`.
  **Fail-closed semantics in tenant-enabled consumers (P1 pin):** when a
  consumer runs in tenant mode with a resolver BOUND, `''` means "missing
  tenant context", not "sentinel store" — the consumer MUST throw/403 rather
  than scope or stamp sentinel rows (a request that slipped past tenant
  resolution must never read or write the `''` partition). The sentinel is a
  valid scoping key ONLY in non-tenant mode. Explicit system paths (the
  adopt command, cross-tenant maintenance CLI that iterates tenants
  deliberately) are the sole exemptions, and each names itself as such.
- **`TenantTableRegistry`** — `register(list<string> $tables): void`.
  Replaces consumers writing into `tenancy.tables` config: a tenant-aware
  extension registers its tenant-owned tables at boot through the contract,
  and tenancy feeds them into its authoritative raw-query backstop registry.
  Idempotent (re-registering is a no-op).

### `Glueful\Extensions\Contracts\Payments\`

- **`PayableReference`** — readonly VO: `string $type` (e.g.
  `'commerce_order'`), `string $id`, `int $amount` (minor units),
  `string $currency`, `?string $description = null`,
  `array $metadata = []`. Matches payvia's existing polymorphic
  `payable_type`/`payable_id` columns.
- **`PaymentInitiation`** — readonly VO: `string $provider`,
  `string $status` (`'ok'|'manual'`), `array $payload` (whatever the client
  needs to proceed — gateway checkout URL, reference, or manual
  instructions).
- **`PaymentCollector`** —
  `initiate(ApplicationContext $context, PayableReference $payable): PaymentInitiation`.
  **Idempotency pin carried over from the commerce spec:** repeat calls for
  the same `(type, id)` return/refresh the same logical intent, never
  double-charge. Throwing = initiation failure (the consumer defines resume
  semantics).
- **`PaymentConfirmation`** — readonly VO (P1 pin — handlers need STRUCTURED
  verified facts, not a raw payload): `string $status` (`'paid'` is the only
  value that may reach handlers), `string $reference`, `int $amount`
  (**minor units** — the implementing provider owns the conversion from its
  gateway's native representation), `string $currency`,
  `array $providerPayload = []`.
- **`PaymentConfirmationHandler`** — the inverse direction (provider →
  payable owner):
  - `supports(string $payableType): bool`
  - `confirmed(ApplicationContext $context, PayableReference $payable, PaymentConfirmation $confirmation): void`
  - Constant `CONTAINER_TAG = 'extension_contracts.payment_confirmation_handlers'`
    — implementers tag their handler service; the payment provider resolves
    all tagged handlers and dispatches to those whose `supports()` matches.
  - **Success-only dispatch (P1 pin):** providers dispatch ONLY for verified
    successful payments — never on method-level "completed" (payvia's
    `confirmAndRecord()` returns normally for failed verifications; keying
    dispatch off that would let a failed verification mark an order paid).
  - The OWNER decides business meaning AND must verify the money: commerce's
    handler compares `confirmation->amount`/`currency` against the order's
    `grand_total`/`currency` before `markPaid` — mismatch records an order
    event (`payment_amount_mismatch`) and does NOT transition; late arrival
    (order no longer `pending_payment`) routes to the late-rejection path.

Deliberately absent from v1 (YAGNI until a second consumer needs them):
refund/webhook contracts, a shared `Money` VO, user/customer identity
contracts (the framework's `UserProviderInterface` already covers identity),
shared event classes (the handler tag replaces cross-package event coupling
for confirmations).

## 4. Tenancy adoption (in scope)

In `glueful/tenancy`:

- `composer require glueful/extension-contracts`.
- Bind `CurrentTenantResolver` → new `ContractTenantResolver` wrapping
  `TenantContext` (`''` when no active tenant).
- Bind `TenantTableRegistry` → implementation that merges registered tables
  into the same authoritative set the config `tenancy.tables` list feeds
  (config list and contract registrations union; both protect the raw-query
  backstop).
- Existing `tenancy.tables` config behavior unchanged — the contract is an
  additive registration channel.

## 5. Payvia adoption (in scope)

In `glueful/payvia`:

- `composer require glueful/extension-contracts`.
- **New capability interface** (payvia-owned, since it is about payvia's
  gateway internals): `InitiationCapableGateway` —
  `initialize(PayableReference $payable, array $options = []): array`
  (gateway checkout payload) — the `SubscriptionCapableGateway` /
  `WebhookCapableGateway` pattern; Paystack implements it first
  (transaction-initialize endpoint).
- Bind `PaymentCollector` → new `PayviaPaymentCollector`: resolves the
  default gateway; if it is initiation-capable, initialize; otherwise return
  a `PaymentInitiation('payvia', 'manual', [...])` so collection still
  resolves.
- **Initiation idempotency is a STORAGE invariant, not a method promise
  (P1 pin):** payvia's current repository only supports `findByReference()`
  — there is no payable-keyed lookup to make repeat `initiate()` calls safe.
  Payvia adds a pending-intent record with a portable uniqueness invariant
  over `(payable_type, payable_id)` for OPEN initiations — the
  discriminator-column strategy (uniqueness key = `type:id` while open,
  re-keyed with the reference on close; MySQL has no partial indexes), plus
  the repository methods to find/reuse an open intent. A test proves two
  `initiate()` calls for the same payable yield ONE provider reference.
- **Confirmation dispatch:** when `confirmAndRecord()` yields a VERIFIED
  SUCCESSFUL payment (§3 success-only pin — never on mere method completion)
  carrying a `payable_type`, payvia builds a `PaymentConfirmation` (amount
  normalized to integer minor units — payvia's verify currently floats it;
  the adoption owns this conversion per gateway) and calls `confirmed()` on
  every `CONTAINER_TAG`-tagged handler whose `supports($payableType)` is
  true — payvia stops needing to know who owns payables at all. (Verify the
  container's tagged-service API against the framework before implementation;
  the import-export adapter registry in lemma uses the same mechanism.)

## 6. Commerce plan amendment (consumes only contracts)

The commerce plan (`extensions/commerce/docs/superpowers/plans/2026-07-05-commerce.md`)
changes as follows — no payvia or tenancy class name remains anywhere in
commerce:

- `composer require glueful/extension-contracts` joins Task 1;
  `CurrentTenantResolver` + `SentinelTenantResolver` are no longer defined by
  commerce — Task 1 keeps only the sentinel FALLBACK class (implementing the
  shared contract) and binds nothing under the contract id (§2 rule).
- Task 8: `PaymentCollector`/`PaymentInitiation` come from the contracts
  package; `ManualPaymentCollector` implements the shared contract;
  `initiate()` takes a `PayableReference` built from the order
  (`type: 'commerce_order'`, `id: order uuid`, `amount: grand_total`).
- Task 10: `CheckoutService` receives its collector via a factory applying
  the soft-binding rule (`has() ? get() : new ManualPaymentCollector()`).
- Task 11 becomes "confirmation handler + decoupling": commerce ships
  `OrderPaymentConfirmationHandler` (implements
  `PaymentConfirmationHandler`, `supports('commerce_order')`) tagged under
  `CONTAINER_TAG` — replacing the payvia-event listener and the
  `class_exists` probe entirely. Per the §3 owner-verifies-money pin it
  compares `confirmation->amount`/`currency` to the order's
  `grand_total`/`currency` (mismatch → `payment_amount_mismatch` order
  event, NO transition) and routes `pending_payment` → `markPaid`, anything
  else → `rejectLatePayment`.
- Task 15: tenant mode uses the contracts — enabled + no
  `CurrentTenantResolver` bound = the loud boot error; table registration
  goes through `TenantTableRegistry` when bound (no more
  `mergeConfig('tenancy', ...)`). **Fail-closed pin (§3):** in tenant mode,
  a bound resolver returning `''` on a scoped path throws (HTTP surfaces map
  it to 403) — sentinel reads/writes are impossible outside non-tenant mode;
  only `commerce:tenancy:adopt` and explicitly cross-tenant maintenance CLI
  bypass, by name.
- `commerce:diagnose` reports per-contract: bound implementation FQCN or
  "fallback (…)" — unchanged in spirit, now purely contract-keyed.

## 7. Testing

- Contracts package: interface/VO shape tests only (readonly construction,
  defaults) — it is intentionally almost logic-free.
- Tenancy: resolver returns active tenant uuid / `''`; registry-registered
  tables are protected by the raw-query backstop exactly like config-listed
  ones.
- Payvia: collector idempotency proven at the STORAGE level (two initiates →
  one open intent row, one provider reference); non-capable gateway →
  `'manual'` initiation; confirmation dispatch reaches a tagged fake handler
  with matching `supports()`, skips non-matching, and NEVER fires for a
  failed verification (the success-only pin, tested with a failing-verify
  gateway stub); dispatched `PaymentConfirmation.amount` is integer minor
  units.
- Commerce (amended plan tests): decoupling invariant becomes "nothing bound
  under the contract ids → manual/sentinel fallbacks" — asserted WITHOUT any
  payvia/tenancy class reference; tenant mode + bound resolver returning `''`
  → scoped paths throw (fail-closed); confirmation with mismatched
  amount/currency → `payment_amount_mismatch` event and order still
  `pending_payment`.

## 8. Out of scope

Refund/dispute contracts; webhook signature contracts; shared Money VO;
customer identity contracts; contracts for search/media/notifications (each
waits for its second consumer); back-porting subscriptions' payvia bridge to
these contracts (works as-is; migrate opportunistically later).
