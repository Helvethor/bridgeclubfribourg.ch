<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Turnament\Calendar;
use PHPUnit\Framework\TestCase;

class CalendarTest extends TestCase
{
    public function testGetNavigationReturnsFiveMonths(): void
    {
        $navigation = Calendar::getNavigation(2026, 5);

        $this->assertIsArray($navigation);
        $this->assertCount(5, $navigation);

        $months = array_map(fn($item) => (int) $item['date']->format('m'), $navigation);
        $this->assertSame([3, 4, 5, 6, 7], $months);

        $this->assertSame('active', $navigation[2]['class']);
    }
}
