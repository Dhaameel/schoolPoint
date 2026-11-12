<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    #[Test]
    public function amounts_are_normalized_without_floats(): void
    {
        $this->assertSame('12.50', Money::normalize('12.5'));
        $this->assertSame('100.00', Money::normalize('100'));
        $this->assertSame('0.50', Money::normalize('.5'));
        $this->assertSame('0.50', Money::normalize('000.50'));
        $this->assertSame('9999999999.99', Money::normalize('9999999999.99'));
        $this->assertSame('0.00', Money::normalize('1e3'));
        $this->assertSame('0.00', Money::normalize('twelve'));
    }

    #[Test]
    public function amounts_are_formatted_with_a_currency(): void
    {
        $this->assertSame('1,250.00 USD', Money::format('1250.00', 'USD'));
        $this->assertSame('25.50', Money::format('25.5'));
    }
}
