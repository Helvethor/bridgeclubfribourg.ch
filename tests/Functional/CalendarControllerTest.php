<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CalendarControllerTest extends WebTestCase
{
    public function testDefaultCalendarView(): void
    {
        $client = static::createClient();
        $client->request('GET', '/turnament');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('table, .calendar, div');
    }

    public function testSpecificMonthCalendarView(): void
    {
        $client = static::createClient();
        $client->request('GET', '/turnament/2026/05');

        $this->assertResponseIsSuccessful();
    }

    public function testNextYearCalendarSelection(): void
    {
        $nextYear = (int) (new \DateTimeImmutable())->format('Y') + 1;
        $client = static::createClient();
        $client->request('GET', "/turnament/$nextYear/05");

        $this->assertResponseIsSuccessful();
        $this->assertSelectorCount($nextYear - 2015 + 1, '#calendarYearSelect option');
        $this->assertSelectorTextSame('#calendarYearSelect option:first-child', '2015');
        $this->assertSelectorTextSame('#calendarYearSelect option:last-child', (string) $nextYear);
        $this->assertSelectorTextSame('#calendarYearSelect option[selected]', (string) $nextYear);
        $this->assertSelectorExists("#calendarYearSelect option[selected][value='/turnament/$nextYear/05']");
        $this->assertSelectorExists("#calendarMonthSelect option[value='/turnament/$nextYear/06']");
    }
}
