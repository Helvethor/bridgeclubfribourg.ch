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
}
