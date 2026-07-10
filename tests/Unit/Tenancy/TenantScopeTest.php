<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Tests\Unit\Tenancy;

use Glueful\Bootstrap\ApplicationContext;
use Glueful\Extensions\Contracts\Tenancy\CurrentTenantResolver;
use Glueful\Extensions\Contracts\Tenancy\TenantContextRequiredException;
use Glueful\Extensions\Contracts\Tenancy\TenantScope;
use PHPUnit\Framework\TestCase;

final class TenantScopeTest extends TestCase
{
    private function ctx(): ApplicationContext
    {
        // ApplicationContext is final; TenantScope never dereferences it (only passes it through),
        // so an uninitialised instance is sufficient for this unit test.
        return (new \ReflectionClass(ApplicationContext::class))->newInstanceWithoutConstructor();
    }

    public function testNullResolverMeansSingleTenant(): void
    {
        self::assertNull(TenantScope::current(null, $this->ctx()));
    }

    public function testResolvedUuidIsReturned(): void
    {
        $resolver = new class implements CurrentTenantResolver {
            /** @inheritDoc */
            public function tenantUuid(ApplicationContext $context): string
            {
                return 'ten000000001';
            }
        };
        self::assertSame('ten000000001', TenantScope::current($resolver, $this->ctx()));
    }

    public function testEmptyTenantFailsClosed(): void
    {
        $resolver = new class implements CurrentTenantResolver {
            /** @inheritDoc */
            public function tenantUuid(ApplicationContext $context): string
            {
                return '';
            }
        };
        $this->expectException(TenantContextRequiredException::class);
        TenantScope::current($resolver, $this->ctx());
    }
}
