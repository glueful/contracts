<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Tests\Unit\Email;

use Glueful\Extensions\Contracts\Email\EmailTemplateDefinition;
use Glueful\Extensions\Contracts\Email\EmailTemplatePlaceholder;
use PHPUnit\Framework\TestCase;

final class ValueObjectsTest extends TestCase
{
    public function testPlaceholderShape(): void
    {
        $placeholder = new EmailTemplatePlaceholder(
            'app_name',
            'The application name.',
            'Glueful'
        );

        self::assertSame('app_name', $placeholder->name);
        self::assertSame('The application name.', $placeholder->description);
        self::assertSame('Glueful', $placeholder->sample);
    }

    public function testDefinitionShapeAndDefaults(): void
    {
        $placeholder = new EmailTemplatePlaceholder('app_name', 'The app name.', 'Glueful');
        $definition = new EmailTemplateDefinition(
            key: 'lemma.comment-reply',
            label: 'Comment reply',
            description: 'A comment reply notification.',
            defaultSubject: 'Reply from {{app_name}}',
            defaultBody: '<p>Hello</p>',
            placeholders: [$placeholder],
            owner: 'glueful/lemma'
        );

        self::assertSame('lemma.comment-reply', $definition->key);
        self::assertSame('Comment reply', $definition->label);
        self::assertSame([$placeholder], $definition->placeholders);
        self::assertSame('glueful/lemma', $definition->owner);
    }

    /**
     * @dataProvider validKeyProvider
     */
    public function testDefinitionAcceptsValidKeys(string $key): void
    {
        $definition = new EmailTemplateDefinition(
            key: $key,
            label: 'Label',
            description: 'Description',
            defaultSubject: 'Subject',
            defaultBody: 'Body'
        );

        self::assertSame($key, $definition->key);
    }

    /**
     * @return iterable<string,array{string}>
     */
    public static function validKeyProvider(): iterable
    {
        yield 'bare' => ['verification'];
        yield 'dotted' => ['lemma.comment-reply'];
        yield 'underscore' => ['password_reset'];
        yield 'dash' => ['password-reset'];
        yield 'number' => ['a1'];
    }

    /**
     * @dataProvider invalidKeyProvider
     */
    public function testDefinitionRejectsInvalidKeys(string $key): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('[a-z0-9][a-z0-9._-]*');

        new EmailTemplateDefinition(
            key: $key,
            label: 'Label',
            description: 'Description',
            defaultSubject: 'Subject',
            defaultBody: 'Body'
        );
    }

    /**
     * @return iterable<string,array{string}>
     */
    public static function invalidKeyProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'uppercase' => ['Bad'];
        yield 'leading dash' => ['-x'];
        yield 'space' => ['a b'];
    }
}
