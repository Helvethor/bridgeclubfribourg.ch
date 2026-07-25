<?php

declare(strict_types=1);

namespace App\Turnament;

class DateUtils {

    /**
     * @param array<mixed> $dateVars
     * @return array<mixed>
     */
    public static function defaults(array $dateVars): array
    {
		$now = new \DateTime();
        if (isset($dateVars['day']) && $dateVars['day'] == 'current')
            $dateVars['day'] = $now->format('d');
        if (isset($dateVars['month']) && $dateVars['month'] == 'current')
            $dateVars['month'] = $now->format('m');
        if (isset($dateVars['year']) && $dateVars['year'] == 'current')
            $dateVars['year'] = $now->format('Y');
        return $dateVars;
    }
}

?>
