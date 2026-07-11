<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Tenancy;

/** A host is reserved after release; only its availability time is public. */
final class HostCooldownException extends \DomainException
{
    public function __construct(private readonly string $availableAfter)
    {
        parent::__construct('Host is in cooldown and cannot be claimed yet.');
    }

    public function availableAfter(): string
    {
        return $this->availableAfter;
    }
}
