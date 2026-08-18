<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Tests\Unit\Schema;

use PHPUnit\Framework\TestCase;

final class SchemaManifestTest extends TestCase
{
    public function testDeclaresSchemaFreeManifest(): void
    {
        $composer = json_decode((string) file_get_contents(dirname(__DIR__, 3) . '/composer.json'), true);
        $glueful = $composer['extra']['glueful'];

        self::assertSame('none', $glueful['migrations'], 'an empty schema declares "none" explicitly');
        self::assertSame([], $glueful['requires']['extensions']);
        self::assertDirectoryDoesNotExist(dirname(__DIR__, 3) . '/migrations');
    }
}
