<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Tests\Unit\Tenancy;

use Glueful\Extensions\Contracts\Tenancy\TenantContextRunner;
use PHPUnit\Framework\TestCase;

final class TenantContextRunnerContractTest extends TestCase
{
    public function testFakeRunnerHonoursTheContract(): void
    {
        $runner = new class implements TenantContextRunner {
            /** @var list<string> */
            public array $seen = [];

            public function runAsTenant(string $tenantUuid, callable $fn): mixed
            {
                $this->seen[] = "tenant:$tenantUuid";
                return $fn();
            }

            public function runAsSystem(callable $fn): mixed
            {
                $this->seen[] = 'system';
                return $fn();
            }

            public function forEachTenant(callable $fn): void
            {
                foreach (['a', 'b'] as $uuid) {
                    $fn($uuid);
                }
            }
        };

        self::assertSame(42, $runner->runAsTenant('t1', static fn (): int => 42));
        self::assertSame('ok', $runner->runAsSystem(static fn (): string => 'ok'));

        $uuids = [];
        $runner->forEachTenant(static function (string $u) use (&$uuids): void {
            $uuids[] = $u;
        });

        self::assertSame(['a', 'b'], $uuids);
        self::assertSame(['tenant:t1', 'system'], $runner->seen);
    }
}
