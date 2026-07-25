<?php

declare(strict_types=1);

namespace App\Turnament;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Turnament;
use App\Entity\RegistrationBoard;

class Calendar
{

	public function __construct(private readonly EntityManagerInterface $em)
    {
    }

	public function getCalendar(int $year, int $month): array
	{
		$turnaments = $this->em->getRepository(Turnament::class)->findByMonth($year, $month);
		$turnaments = $this->dayIndex($turnaments);
        $registrationBoards = $this->em->getRepository(RegistrationBoard::class)->findByMonth($year, $month);
		$registrationBoards = $this->dayIndex($registrationBoards);

		$firstOfMonth = \DateTime::createFromFormat('d/m/Y H:i:s', "01/$month/$year 00:00:00");
		$weekStart = (int) $firstOfMonth->format('N') - 1;
		$nbDays = (int) $firstOfMonth->format('t');

		$weeksCount = 0;
		$daysCount = 1 - $weekStart;
		$currentDay = clone $firstOfMonth;
		$dayOffset = 0;
		$calendar = [];

		while ($daysCount < $nbDays || $dayOffset != 0) {
			if ($daysCount < 1 || $daysCount > $nbDays)
				$calendar[$weeksCount][$dayOffset] = [];
			else {
				$calendar[$weeksCount][$dayOffset]['date'] = clone $currentDay;

				if (isset($turnaments[$daysCount]))
					$calendar[$weeksCount][$dayOffset]['turnament'] = $turnaments[$daysCount];
                else if (isset($registrationBoards[$daysCount])) 
					$calendar[$weeksCount][$dayOffset]['registrationBoard'] = $registrationBoards[$daysCount];

				$currentDay->modify('+1 day');
			}

			$daysCount += 1;
			$dayOffset = ($dayOffset + 1) % 7;

			if ($dayOffset == 0)
				$weeksCount += 1;
		}
		
		return $calendar;
	}

	private function dayIndex(array $items): array
	{
        $index = [];

		foreach ($items as $item) {
			$day = (int) $item->getDate()->format('d');
			$index[$day] = $item;
		}

		return $index;
	}

	public static function getNavigation(int $year, int $month): array
	{
        $nav = [];

		$date = new \DateTime("$year-$month-01");
        $date->modify('first day of -2 month');
        $nav[] = ['date' => clone $date, 'class' => ''];
        $date->modify('first day of next month');
        $nav[] = ['date' => clone $date, 'class' => ''];
        $date->modify('first day of next month');
        $nav[] = ['date' => clone $date, 'class' => 'active'];
        $date->modify('first day of next month');
        $nav[] = ['date' => clone $date, 'class' => ''];
        $date->modify('first day of next month');
        $nav[] = ['date' => clone $date, 'class' => ''];

        return $nav;
	}
}
