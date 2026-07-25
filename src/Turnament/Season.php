<?php

declare(strict_types=1);

namespace App\Turnament;

use App\Entity\Player;

class Season
{
    public static function getActual(): int|string
    {
		$now = new \DateTime();
		$fromYear = $now->format('Y');

		$switchDate = \DateTime::createFromFormat('d/m/Y H:i:s', "01/07/$fromYear 00:00:00");

		if ($now < $switchDate)
		{
			$fromYear -= 1;	
		}

		return $fromYear;
	}

    /**
     * @return array<string, \DateTime|false>
     */
	public static function getFromToDates(int $fromYear, int $toYear): array
	{
		$from = \DateTime::createFromFormat(
			'd/m/Y H:i:s', 
			"01/07/$fromYear 00:00:00" 
		);
		$to = \DateTime::createFromFormat(
			'd/m/Y H:i:s', 
			"30/06/$toYear 23:59:59" 
		);

		return [
			'from' => $from,
			'to' => $to
		];
	}
}

?>
