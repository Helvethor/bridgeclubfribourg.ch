<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Turnament\Season;
use PHPUnit\Framework\TestCase;

class SeasonTest extends TestCase
{
    public function testGetActualReturnsNumericYear(): void
    {
        $actual = Season::getActual();
        $this->assertIsNumeric($actual);

        $now = new \DateTime();
        $currentYear = (int) $now->format('Y');
        $this->assertGreaterThanOrEqual($currentYear - 1, (int) $actual);
        $this->assertLessThanOrEqual($currentYear, (int) $actual);
    }

    public function testGetFromToDates(): void
    {
        $dates = Season::getFromToDates(2025, 2026);

        $this->assertArrayHasKey('from', $dates);
        $this->assertArrayHasKey('to', $dates);

        $this->assertInstanceOf(\DateTime::class, $dates['from']);
        $this->assertInstanceOf(\DateTime::class, $dates['to']);

        $this->assertSame('2025-07-01 00:00:00', $dates['from']->format('Y-m-d H:i:s'));
        $this->assertSame('2026-06-30 23:59:59', $dates['to']->format('Y-m-d H:i:s'));
    }
}
