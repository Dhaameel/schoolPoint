<?php

namespace Tests\Unit;

use App\Support\Csv;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CsvTest extends TestCase
{
    #[Test]
    public function dangerous_cells_are_prefixed(): void
    {
        foreach (['=SUM(A1)', '+cmd', '-2+3', '@user', "\t=1", "\r=1"] as $value) {
            $this->assertSame("'".$value, Csv::safe($value));
        }

        $this->assertSame('Normal', Csv::safe('Normal'));
        $this->assertSame('', Csv::safe(''));
    }
}
