<?php

declare(strict_types=1);

namespace Glueful\Extensions\Contracts\Tenancy;

use Glueful\Routing\RouteMiddleware;

/** Neutral request-time tenant resolver middleware implemented by a tenancy extension. */
interface TenantRequestMiddleware extends RouteMiddleware
{
}
