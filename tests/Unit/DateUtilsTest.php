<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Turnament\DateUtils;
use PHPUnit\Framework\TestCase;

class DateUtilsTest extends TestCase
{
    public function testDefaultsWithExplicitValues(): void
    {
        $input = [
            'day' => '15',
            'month' => '08',
            'year' => '2025',
        ];

        $result = DateUtils::defaults($input);

        $this->assertSame('15', $result['day']);
        $this->assertSame('08', $result['month']);
        $this->assertSame('2025', $result['year']);
    }

    public function testDefaultsWithCurrentPlaceholders(): void
    {
        $input = [
            'day' => 'current',
            'month' => 'current',
            'year' => 'current',
        ];

        $now = new \DateTime();
        $result = DateUtils::defaults($input);

        $this->assertSame($now->format('d'), $result['day']);
        $this->assertSame($now->format('m'), $result['month']);
        $this->assertSame($now->format('Y'), $result['year']);
    }

    public function testDefaultsWithEmptyArray(): void
    {
        $result = DateUtils::defaults([]);
        $this->assertSame([], $result);
    }
}
