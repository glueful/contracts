<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Email;

/**
 * Registry for email template definitions declared by installed extensions.
 *
 * Implementations must allow re-registering a key only when the new
 * definition has the same owner as the existing definition. A different owner
 * claiming an existing key must fail loudly during boot; provider order must
 * never decide which definition wins.
 */
interface EmailTemplateRegistry
{
    public function register(EmailTemplateDefinition ...$definitions): void;

    /**
     * @return list<EmailTemplateDefinition>
     */
    public function all(): array;

    public function find(string $key): ?EmailTemplateDefinition;
}
