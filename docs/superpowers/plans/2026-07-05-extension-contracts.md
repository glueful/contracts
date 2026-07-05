# Extension Contracts Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship `glueful/extension-contracts` (shared tenancy + payment seams) and make tenancy and payvia bind them, so commerce (and future extensions) compose through interfaces instead of each other's class names.

**Architecture:** One inert contracts package (interfaces + readonly VOs + a container-tag constant, hard framework require). Tenancy binds `CurrentTenantResolver` + `TenantTableRegistry` over its existing internals. Payvia gains an `InitiationCapableGateway` capability, a `payment_intents` storage-level idempotency record, a `PayviaPaymentCollector`, and success-only confirmation dispatch to tagged `PaymentConfirmationHandler`s.

**Tech Stack:** PHP 8.3, Glueful framework `^1.65.3` (hard require in contracts; tenancy/payvia keep their own floors), PHPUnit 10.5, PSR-12, PHPStan level 5.

**Spec:** `docs/superpowers/specs/2026-07-05-extension-contracts-design.md`

## Global Constraints

- Contracts package namespace: `Glueful\Extensions\Contracts\` (framework owns `Glueful\Contracts\` — do not use it).
- Contracts package contains interfaces, readonly VOs, constants ONLY — no provider, no config, no migrations, no logic beyond VO construction.
- Soft-binding precedence rule (spec §2): consumers never bind defaults under shared contract ids; only implementers bind.
- `CurrentTenantResolver::tenantUuid()` returns `''` for "no active tenant"; tenant-enabled consumers treat a bound resolver returning `''` as fail-closed (that enforcement lives in consumers — commerce plan Task 15).
- Confirmation dispatch is SUCCESS-ONLY (verified paid), with `PaymentConfirmation.amount` in integer minor units.
- Payvia initiation idempotency is a storage invariant: unique discriminator key over `(payable_type, payable_id)` while an intent is open.
- Unreleased local packages wire via composer path repositories (`{"type": "path", "url": "../contracts"}`); version pins are finalized at release time (release-before-pinning rule).
- Commit per task, per repo; no attribution trailers. Tags/releases are the user's.

## Repos touched

| Phase | Repo (working directory) |
| --- | --- |
| A (Task 1) | `/Users/michaeltawiahsowah/Sites/glueful/extensions/contracts` |
| B (Task 2) | `/Users/michaeltawiahsowah/Sites/glueful/extensions/tenancy` |
| C (Tasks 3–5) | `/Users/michaeltawiahsowah/Sites/glueful/extensions/payvia` |

(The commerce-plan amendments from spec §6 are applied directly to `extensions/commerce/docs/superpowers/plans/2026-07-05-commerce.md` — already done in-session, not a task here.)

---

### Task 1: The contracts package

**Repo:** `extensions/contracts`

**Files:**
- Create: `composer.json`, `phpunit.xml`, `.gitignore`, `README.md`
- Create: `src/Tenancy/CurrentTenantResolver.php`, `src/Tenancy/TenantTableRegistry.php`
- Create: `src/Payments/PayableReference.php`, `src/Payments/PaymentInitiation.php`, `src/Payments/PaymentConfirmation.php`, `src/Payments/PaymentCollector.php`, `src/Payments/PaymentConfirmationHandler.php`
- Test: `tests/Unit/Payments/ValueObjectsTest.php`

**Interfaces (the package IS its interface — verbatim):**

`src/Tenancy/CurrentTenantResolver.php`:

```php
<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Tenancy;

use Glueful\Bootstrap\ApplicationContext;

/**
 * The active tenant, or '' when none is resolved. NEVER null — '' doubles as
 * the single-store sentinel scoping key in NON-tenant mode.
 *
 * Fail-closed pin (spec §3): a consumer running in tenant mode with this
 * contract BOUND must treat '' as "missing tenant context" and throw/403 —
 * never scope or stamp the '' partition. Only explicitly-named system paths
 * (adopt/maintenance commands) may bypass.
 */
interface CurrentTenantResolver
{
    public function tenantUuid(ApplicationContext $context): string;
}
```

`src/Tenancy/TenantTableRegistry.php`:

```php
<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Tenancy;

/**
 * Registration channel for tenant-owned tables — replaces consumers writing
 * into another package's config. Idempotent: re-registering is a no-op.
 * The implementing tenancy package feeds these into its authoritative
 * raw-query backstop registry.
 */
interface TenantTableRegistry
{
    /** @param list<string> $tables */
    public function register(array $tables): void;
}
```

`src/Payments/PayableReference.php`:

```php
<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Payments;

/**
 * What is being paid for. Matches the polymorphic payable_type/payable_id
 * convention. $amount is INTEGER MINOR UNITS, always.
 */
final class PayableReference
{
    /** @param array<string,mixed> $metadata */
    public function __construct(
        public readonly string $type,
        public readonly string $id,
        public readonly int $amount,
        public readonly string $currency,
        public readonly ?string $description = null,
        public readonly array $metadata = [],
    ) {
    }
}
```

`src/Payments/PaymentInitiation.php`:

```php
<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Payments;

/**
 * What the storefront hands the client to proceed with payment.
 * $status: 'ok' (a live gateway flow is in $payload) or 'manual'
 * (no automated collection; $payload carries instructions).
 */
final class PaymentInitiation
{
    /** @param array<string,mixed> $payload */
    public function __construct(
        public readonly string $provider,
        public readonly string $status,
        public readonly array $payload = [],
    ) {
    }
}
```

`src/Payments/PaymentConfirmation.php`:

```php
<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Payments;

/**
 * Structured VERIFIED payment facts (spec §3 P1): handlers receive typed
 * money they can compare against the payable — never just a raw payload.
 * $status is 'paid' — providers dispatch success-only, but the field stays
 * explicit so a handler can assert it. $amount is INTEGER MINOR UNITS; the
 * implementing provider owns conversion from its gateway's representation.
 */
final class PaymentConfirmation
{
    /** @param array<string,mixed> $providerPayload */
    public function __construct(
        public readonly string $status,
        public readonly string $reference,
        public readonly int $amount,
        public readonly string $currency,
        public readonly array $providerPayload = [],
    ) {
    }
}
```

`src/Payments/PaymentCollector.php`:

```php
<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Payments;

use Glueful\Bootstrap\ApplicationContext;

/**
 * Starts collection for a payable. IDEMPOTENT per (type, id): repeat calls
 * return/refresh the same logical intent — never a second charge. Throwing
 * signals initiation failure; the consumer owns resume semantics.
 */
interface PaymentCollector
{
    public function initiate(ApplicationContext $context, PayableReference $payable): PaymentInitiation;
}
```

`src/Payments/PaymentConfirmationHandler.php`:

```php
<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Payments;

use Glueful\Bootstrap\ApplicationContext;

/**
 * The inverse direction: a payment provider notifies the payable's OWNER of a
 * verified successful payment. Implementers tag their service with
 * CONTAINER_TAG; providers dispatch to every handler whose supports() matches.
 *
 * Providers dispatch SUCCESS-ONLY (spec §3 P1). The owner verifies the money
 * (amount/currency vs the payable) and decides business meaning — including
 * late/mismatched arrivals.
 */
interface PaymentConfirmationHandler
{
    public const CONTAINER_TAG = 'extension_contracts.payment_confirmation_handlers';

    public function supports(string $payableType): bool;

    public function confirmed(
        ApplicationContext $context,
        PayableReference $payable,
        PaymentConfirmation $confirmation
    ): void;
}
```

`composer.json`:

```json
{
    "name": "glueful/extension-contracts",
    "description": "Shared cross-extension contracts for the Glueful ecosystem: tenancy and payment seams.",
    "type": "library",
    "license": "MIT",
    "authors": [{"name": "Michael Tawiah Sowah", "email": "michael@glueful.dev"}],
    "keywords": ["glueful", "contracts", "tenancy", "payments"],
    "require": {
        "php": "^8.3",
        "glueful/framework": "^1.65.3"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.5",
        "squizlabs/php_codesniffer": "^3.6",
        "phpstan/phpstan": "^1.0"
    },
    "autoload": {
        "psr-4": {"Glueful\\Extensions\\Contracts\\": "src/"}
    },
    "autoload-dev": {
        "psr-4": {"Glueful\\Extensions\\Contracts\\Tests\\": "tests/"}
    },
    "scripts": {
        "test": "vendor/bin/phpunit",
        "phpcs": "vendor/bin/phpcs --standard=PSR12 src",
        "analyze": "vendor/bin/phpstan analyze src --level=5"
    },
    "minimum-stability": "stable"
}
```

`phpunit.xml`: the standard pack shape (bootstrap `vendor/autoload.php`, testsuite `Unit` → `tests/Unit`).

`README.md`: one page — what the package is, the soft-binding precedence rule (spec §2, copied), the fail-closed `''` rule, the success-only dispatch rule, who binds what (tenancy/payvia) and who consumes (commerce).

- [ ] **Step 1: Write the failing VO test**

`tests/Unit/Payments/ValueObjectsTest.php`:

```php
<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Tests\Unit\Payments;

use Glueful\Extensions\Contracts\Payments\PayableReference;
use Glueful\Extensions\Contracts\Payments\PaymentConfirmation;
use Glueful\Extensions\Contracts\Payments\PaymentConfirmationHandler;
use Glueful\Extensions\Contracts\Payments\PaymentInitiation;
use PHPUnit\Framework\TestCase;

final class ValueObjectsTest extends TestCase
{
    public function testPayableReferenceDefaults(): void
    {
        $p = new PayableReference('commerce_order', 'ord000000001', 4999, 'USD');
        self::assertNull($p->description);
        self::assertSame([], $p->metadata);
        self::assertSame(4999, $p->amount);
    }

    public function testPaymentInitiationAndConfirmationShapes(): void
    {
        $i = new PaymentInitiation('payvia', 'ok', ['checkout_url' => 'https://x']);
        self::assertSame('ok', $i->status);

        $c = new PaymentConfirmation('paid', 'ref-1', 4999, 'USD', ['raw' => true]);
        self::assertSame('paid', $c->status);
        self::assertSame(4999, $c->amount);
    }

    public function testContainerTagConstant(): void
    {
        self::assertSame(
            'extension_contracts.payment_confirmation_handlers',
            PaymentConfirmationHandler::CONTAINER_TAG
        );
    }
}
```

- [ ] **Step 2: Run to verify failure** — `composer install && vendor/bin/phpunit` → FAIL (classes missing).
- [ ] **Step 3: Create all seven contract files + package files** (code above, verbatim).
- [ ] **Step 4: Run to verify pass** — `vendor/bin/phpunit && composer run phpcs && composer run analyze` → green.
- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat: v1 contracts — tenancy resolver/table registry, payment collector/confirmation seams"
```

---

### Task 2: Tenancy binds the tenancy contracts

**Repo:** `extensions/tenancy`

**Files:**
- Modify: `composer.json` (add path repo `../contracts` + require `glueful/extension-contracts: *@dev` — pin real version at release)
- Create: `src/Bridge/ContractTenantResolver.php`, `src/Bridge/ContractTableRegistry.php`
- Modify: `src/TenancyServiceProvider.php` (bind both in `services()`, `use`-imports)
- Test: `tests/Integration/Bridge/ContractsBridgeTest.php` (follow the pack's existing test-harness conventions — inspect `tests/` first)

**Interfaces:**
- Consumes: contracts Task 1; tenancy internals `Glueful\Extensions\Tenancy\Context\TenantContext::currentTenant(): ?Tenant`, `Glueful\Extensions\Tenancy\Query\TenantTableRegistry::register(string $table)` (static, idempotent by array-key)
- Produces: container bindings `CurrentTenantResolver::class` and `TenantTableRegistry::class` (the CONTRACT FQCNs) — what commerce Task 15 resolves.

**Name-collision note:** tenancy's internal `Query\TenantTableRegistry` and the contract share a short name — alias the contract in imports: `use Glueful\Extensions\Contracts\Tenancy\TenantTableRegistry as TenantTableRegistryContract;`.

- [ ] **Step 1: Write the failing test**

```php
public function testContractResolverReturnsActiveTenantOrEmpty(): void
{
    $resolver = new ContractTenantResolver(/* TenantContext per harness */);
    self::assertSame('', $resolver->tenantUuid($this->appContext()));   // no active tenant

    /* set an active tenant through TenantContext (harness helper) */
    self::assertSame('tenantAAAA01', $resolver->tenantUuid($this->appContext()));
}

public function testContractRegistryFeedsTheBackstop(): void
{
    (new ContractTableRegistry())->register(['commerce_products', 'commerce_orders']);
    self::assertTrue(\Glueful\Extensions\Tenancy\Query\TenantTableRegistry::isTenantOwned('commerce_products'));
    self::assertTrue(\Glueful\Extensions\Tenancy\Query\TenantTableRegistry::isTenantOwned('commerce_orders'));
    // Idempotent re-registration.
    (new ContractTableRegistry())->register(['commerce_products']);
    self::assertTrue(\Glueful\Extensions\Tenancy\Query\TenantTableRegistry::isTenantOwned('commerce_products'));
}
```

(Adapt construction/active-tenant seeding to the pack's harness — read two existing tests in `tests/Integration` first; the internal static registry may need a reset helper between tests, mirroring how existing tenancy tests handle it.)

- [ ] **Step 2: Run to verify failure.**
- [ ] **Step 3: Implement**

`src/Bridge/ContractTenantResolver.php`:

```php
<?php

declare(strict_types=1);

namespace Glueful\Extensions\Tenancy\Bridge;

use Glueful\Bootstrap\ApplicationContext;
use Glueful\Extensions\Contracts\Tenancy\CurrentTenantResolver;
use Glueful\Extensions\Tenancy\Context\TenantContext;

/** Binds the shared contract over tenancy's request-scoped TenantContext. */
final class ContractTenantResolver implements CurrentTenantResolver
{
    public function tenantUuid(ApplicationContext $context): string
    {
        $tenantContext = app($context, TenantContext::class);
        $tenant = $tenantContext->currentTenant();

        return $tenant?->uuid ?? '';
    }
}
```

(Verify the `Tenant` model's uuid accessor — property vs `->uuid` attribute — against `src/Models/Tenant.php` before finalizing.)

`src/Bridge/ContractTableRegistry.php`:

```php
<?php

declare(strict_types=1);

namespace Glueful\Extensions\Tenancy\Bridge;

use Glueful\Extensions\Contracts\Tenancy\TenantTableRegistry as TenantTableRegistryContract;
use Glueful\Extensions\Tenancy\Query\TenantTableRegistry;

/** Contract-facing registration channel into the authoritative backstop set. */
final class ContractTableRegistry implements TenantTableRegistryContract
{
    /** @param list<string> $tables */
    public function register(array $tables): void
    {
        foreach ($tables as $table) {
            if (!is_string($table) || $table === '') {
                throw new \InvalidArgumentException('Tenant table names must be non-empty strings.');
            }
            TenantTableRegistry::register($table);
        }
    }
}
```

Provider `services()` additions (contract FQCN keys, `use`-imports at top):

```php
CurrentTenantResolver::class => ['class' => ContractTenantResolver::class, 'shared' => true],
TenantTableRegistryContract::class => ['class' => ContractTableRegistry::class, 'shared' => true],
```

- [ ] **Step 4: Run the pack's full suite** — `vendor/bin/phpunit && composer run phpcs` → green.
- [ ] **Step 5: Commit** — `git add -A && git commit -m "feat: bind extension-contracts tenancy seams (resolver + table registry)"`

---

### Task 3: Payvia — initiation capability + payment_intents storage

**Repo:** `extensions/payvia`

**Files:**
- Modify: `composer.json` (path repo + require `glueful/extension-contracts`)
- Create: `src/Contracts/InitiationCapableGateway.php`
- Create: `migrations/008_CreatePaymentIntentsTable.php`
- Create: `src/Repositories/PaymentIntentRepository.php`
- Modify: `src/Gateways/PaystackGateway.php` (implement the capability)
- Test: `tests/.../PaymentIntentRepositoryTest.php` (follow the pack's harness)

**Interfaces:**
- Produces:
  - `InitiationCapableGateway` (payvia-owned): `initialize(PayableReference $payable, array $options = []): array` — returns at least `['reference' => string, 'checkout_url' => ?string]`
  - `payment_intents` table: `uuid` (12, unique), `payable_type` (100), `payable_id` (255), `idempotency_key` (255, **unique**) — `"{type}:{id}"` while OPEN, re-keyed to `"{type}:{id}:{reference}"` on close (the discriminator strategy: MySQL has no partial indexes), `gateway` (50), `reference` (100, indexed), `status` (16: `open|closed`), `amount` (bigint minor units), `currency` (10), `payload` json nullable, timestamps
  - `PaymentIntentRepository::findOpen(ApplicationContext $c, string $payableType, string $payableId): ?array`
  - `PaymentIntentRepository::createOpen(c, array $row): bool` — INSERT with `idempotency_key = "{type}:{id}"`; duplicate-key → `false` (caller re-reads the open intent)
  - `PaymentIntentRepository::close(c, string $uuid, string $reference): void` — sets `status = 'closed'`, re-keys `idempotency_key = "{type}:{id}:{reference}"`

- [ ] **Step 1: Failing storage test** — the P1 invariant:

```php
public function testOpenIntentIsUniquePerPayable(): void
{
    $repo = new PaymentIntentRepository(/* per harness */);
    self::assertTrue($repo->createOpen($ctx, $this->intentRow('commerce_order', 'ord1', 'ref-a')));
    self::assertFalse($repo->createOpen($ctx, $this->intentRow('commerce_order', 'ord1', 'ref-b'))); // duplicate open
    self::assertSame('ref-a', $repo->findOpen($ctx, 'commerce_order', 'ord1')['reference']);
}

public function testClosingReleasesTheKeyForANewIntent(): void
{
    $repo = new PaymentIntentRepository(/* per harness */);
    $repo->createOpen($ctx, $this->intentRow('commerce_order', 'ord2', 'ref-a'));
    $open = $repo->findOpen($ctx, 'commerce_order', 'ord2');
    $repo->close($ctx, $open['uuid'], 'ref-a');
    self::assertNull($repo->findOpen($ctx, 'commerce_order', 'ord2'));
    self::assertTrue($repo->createOpen($ctx, $this->intentRow('commerce_order', 'ord2', 'ref-c'))); // key freed
}
```

- [ ] **Step 2: Run to verify failure.**
- [ ] **Step 3: Implement** migration + repository (payvia migration/repo conventions — mirror `001_CreatePaymentsTable.php` and an existing repository). `PaystackGateway::initialize()` calls Paystack's transaction-initialize endpoint using the SAME HTTP-client pattern as its `verify()` (read that method first); amount passed in minor units (Paystack is natively minor-units).
- [ ] **Step 4: Run to verify pass** + pack gates.
- [ ] **Step 5: Commit** — `git add -A && git commit -m "feat: initiation capability + payment_intents idempotency storage"`

---

### Task 4: PayviaPaymentCollector

**Repo:** `extensions/payvia`

**Files:**
- Create: `src/Services/PayviaPaymentCollector.php`
- Modify: `src/PayviaServiceProvider.php` (bind `PaymentCollector::class` → collector, `use`-imports)
- Test: `tests/.../PayviaPaymentCollectorTest.php`

**Interfaces:**
- Consumes: `GatewayManager::gateway(string)`, `InitiationCapableGateway`, `PaymentIntentRepository`, contracts VOs
- Produces: container binding `Glueful\Extensions\Contracts\Payments\PaymentCollector::class`

**Core (verbatim):**

```php
public function initiate(ApplicationContext $context, PayableReference $payable): PaymentInitiation
{
    // Storage-level idempotency (spec §5 P1): one open intent per payable.
    $existing = $this->intents->findOpen($context, $payable->type, $payable->id);
    if ($existing !== null) {
        return new PaymentInitiation('payvia', 'ok', [
            'reference' => $existing['reference'],
            'checkout_url' => $existing['payload']['checkout_url'] ?? null,
            'gateway' => $existing['gateway'],
        ]);
    }

    $gatewayKey = (string) config($context, 'payvia.default_gateway', 'paystack');
    $gateway = $this->gateways->gateway($gatewayKey);

    if (!$gateway instanceof InitiationCapableGateway) {
        return new PaymentInitiation('payvia', 'manual', [
            'instructions' => "Gateway '{$gatewayKey}' does not support hosted initiation; confirm via reference.",
        ]);
    }

    $result = $gateway->initialize($payable);
    $created = $this->intents->createOpen($context, [
        'payable_type' => $payable->type, 'payable_id' => $payable->id,
        'gateway' => $gatewayKey, 'reference' => (string) $result['reference'],
        'amount' => $payable->amount, 'currency' => $payable->currency,
        'payload' => $result,
    ]);
    if (!$created) {
        // Lost a race to a concurrent initiate — reuse the winner's intent.
        $existing = $this->intents->findOpen($context, $payable->type, $payable->id);
        if ($existing !== null) {
            return new PaymentInitiation('payvia', 'ok', [
                'reference' => $existing['reference'],
                'checkout_url' => $existing['payload']['checkout_url'] ?? null,
                'gateway' => $existing['gateway'],
            ]);
        }
    }

    return new PaymentInitiation('payvia', 'ok', [
        'reference' => (string) $result['reference'],
        'checkout_url' => $result['checkout_url'] ?? null,
        'gateway' => $gatewayKey,
    ]);
}
```

- [ ] **Step 1: Failing tests** — two initiates yield one reference (fake capable gateway counts `initialize()` calls: exactly 1); non-capable gateway → `'manual'` status.
- [ ] **Step 2: Run to verify failure.**
- [ ] **Step 3: Implement + bind.**
- [ ] **Step 4: Run to verify pass** + pack gates.
- [ ] **Step 5: Commit** — `git add -A && git commit -m "feat: PayviaPaymentCollector binds the shared PaymentCollector contract"`

---

### Task 5: Success-only confirmation dispatch

**Repo:** `extensions/payvia`

**Files:**
- Create: `src/Services/ConfirmationDispatcher.php`
- Modify: `src/Services/PaymentService.php` (dispatch hook after verified success), `src/PayviaServiceProvider.php` (tagged-iterator binding for handlers)
- Test: `tests/.../ConfirmationDispatchTest.php`

**Interfaces:**
- Consumes: `PaymentConfirmationHandler::CONTAINER_TAG` tagged services — resolved via the framework's tagged-iterator machinery (`TaggedIteratorDefinition`; copy the exact fold pattern from framework `CoreProvider`'s `identity.claims_provider` usage), `PaymentIntentRepository::close()`
- Produces:
  - `ConfirmationDispatcher::dispatch(ApplicationContext $c, string $payableType, string $payableId, PaymentConfirmation $confirmation): void` — iterates tagged handlers, calls `confirmed()` where `supports()` matches; also closes any matching open intent (re-key with the reference)
  - `PaymentService::confirmAndRecord()` gains ONE hook: **only when the verification status maps to success AND the payment carries a `payable_type`** — build `PaymentConfirmation` with `status: 'paid'`, the verified reference, **amount converted to integer minor units** (Paystack verify amounts are already minor units — confirm against `PaystackGateway::verify()` and normalize `(float)` casts to int; other gateways document their own conversion), verified currency, raw payload — then `dispatch()`. Failed/pending verifications change NOTHING (the method already records them; no dispatch).

- [ ] **Step 1: Failing tests:**

```php
public function testDispatchReachesMatchingTaggedHandlerOnSuccessOnly(): void
{
    $handler = new RecordingHandler('commerce_order');   // test double implementing the contract
    /* register under the tag per harness */

    // Failed verification: NO dispatch.
    $this->confirmWithGatewayStub(status: 'failed', payableType: 'commerce_order', payableId: 'ord1');
    self::assertSame([], $handler->calls);

    // Success: dispatched with integer minor units.
    $this->confirmWithGatewayStub(status: 'success', payableType: 'commerce_order', payableId: 'ord1', amount: 4999, currency: 'USD');
    self::assertCount(1, $handler->calls);
    self::assertSame(4999, $handler->calls[0]['confirmation']->amount);
    self::assertSame('paid', $handler->calls[0]['confirmation']->status);
}

public function testNonMatchingHandlerIsSkipped(): void
{
    $handler = new RecordingHandler('lemma_invoice');
    $this->confirmWithGatewayStub(status: 'success', payableType: 'commerce_order', payableId: 'ord2');
    self::assertSame([], $handler->calls);
}

public function testSuccessfulConfirmationClosesTheOpenIntent(): void
{
    /* createOpen for (commerce_order, ord3) */
    $this->confirmWithGatewayStub(status: 'success', payableType: 'commerce_order', payableId: 'ord3');
    self::assertNull($this->intents->findOpen($this->ctx, 'commerce_order', 'ord3'));
}
```

- [ ] **Step 2: Run to verify failure.**
- [ ] **Step 3: Implement.** Read `PaymentService::confirmAndRecord()` fully first — the success predicate must match ITS status mapping (`$status === 'success'` per the gateway verification contract), not method completion (spec §3 P1).
- [ ] **Step 4: Run to verify pass** + full payvia gates (`vendor/bin/phpunit && composer run phpcs && composer run analyze`).
- [ ] **Step 5: Commit** — `git add -A && git commit -m "feat: success-only confirmation dispatch to tagged payable handlers"`

---

## Self-Review Notes (completed)

- **Spec coverage:** §1 package shape → Task 1 (hard framework require in composer.json); §2 precedence rule → README + consumed by the commerce-plan amendments; §3 contracts incl. both P1 pins (fail-closed docblocked on the resolver; success-only + minor units docblocked on the VO/handler) → Task 1; §4 tenancy adoption → Task 2; §5 payvia adoption incl. capability interface, storage-invariant idempotency (P1), success-only dispatch + float→int conversion (P1) → Tasks 3–5; §6 commerce amendments → applied directly to the commerce plan (not a task); §7 tests all present by name; §8 respected.
- **Verify-points for the executor:** tenancy `Tenant` uuid accessor (Task 2); tenancy test-harness active-tenant seeding + static-registry reset (Task 2); Paystack HTTP-client pattern + initialize endpoint (Task 3); framework `TaggedIteratorDefinition` fold API (Task 5); `PaymentService` status mapping (Task 5).
- **Type consistency:** contract FQCNs identical across tasks; `PaymentConfirmation` field order `(status, reference, amount, currency, providerPayload)` used consistently; intent row keys (`payable_type`, `payable_id`, `gateway`, `reference`, `amount`, `currency`, `payload`) consistent between Tasks 3 and 4.
