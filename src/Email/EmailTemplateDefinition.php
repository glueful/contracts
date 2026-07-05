<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Email;

final class EmailTemplateDefinition
{
    /**
     * @param list<EmailTemplatePlaceholder> $placeholders
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $description,
        public readonly string $defaultSubject,
        public readonly string $defaultBody,
        public readonly array $placeholders = [],
        public readonly string $owner = '',
    ) {
        if (preg_match('/\A[a-z0-9][a-z0-9._-]*\z/', $key) !== 1) {
            throw new \InvalidArgumentException(
                "Email template key '{$key}' must match [a-z0-9][a-z0-9._-]*."
            );
        }
    }
}
