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
