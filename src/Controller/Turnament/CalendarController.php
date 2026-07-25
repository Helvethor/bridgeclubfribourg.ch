<?php

declare(strict_types=1);

namespace App\Controller\Turnament;

use App\Turnament\Calendar;
use App\Turnament\DateUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CalendarController extends AbstractController
{
    public function __construct(private readonly \App\Turnament\Calendar $calendar)
    {
    }

    #[Route(
        '/turnament/{year}/{month}',
        name: 'turnament_calendar',
        defaults: ['month' => 'current', 'year' => 'current'],
        requirements: ['month' => '\d{2}', 'year' => '\d{4}']
    )]
    public function calendar(string $year, string $month): Response
    {
        ['year' => $year, 'month' => $month] = DateUtils::defaults(compact('year', 'month'));
        $year = (int) $year;
        $month = (int) $month;

        $cal        = $this->calendar->getCalendar($year, $month);
        $date       = \DateTime::createFromFormat('Y/m', "$year/$month");

        return $this->render('turnament/calendar.html.twig', [
            'calendar'   => $cal,
            'date'       => $date,
        ]);
    }
}
