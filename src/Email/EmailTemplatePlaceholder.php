<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Email;

final class EmailTemplatePlaceholder
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly string $sample,
    ) {
    }
}
