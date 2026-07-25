<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class FrenchDateExtension extends AbstractExtension
{
    private const array MONTH_TRANSLATIONS = [
        1  => 'Janvier',
        2  => 'Février',
        3  => 'Mars',
        4  => 'Avril',
        5  => 'Mai',
        6  => 'Juin',
        7  => 'Juillet',
        8  => 'Août',
        9  => 'Septembre',
        10 => 'Octobre',
        11 => 'Novembre',
        12 => 'Décembre',
    ];

    private const array DOW_TRANSLATIONS = [
        'monday'    => 'lundi',
        'tuesday'   => 'mardi',
        'wednesday' => 'mercredi',
        'thursday'  => 'jeudi',
        'friday'    => 'vendredi',
        'saturday'  => 'samedi',
        'sunday'    => 'dimanche',
    ];

    public function getFilters(): array
    {
        return [
            new TwigFilter('frenchDate', $this->frenchDateFilter(...)),
            new TwigFilter('frenchMonth', $this->frenchMonthFilter(...)),
            new TwigFilter('frenchDow', $this->frenchDowFilter(...)),
        ];
    }

    public function frenchDateFilter(\DateTimeInterface $date, string $format = 'full'): string
    {
        $formatter = new \IntlDateFormatter('fr_FR', $this->getFormatStyle($format), \IntlDateFormatter::NONE);
        
        return $formatter->format($date) ?: '';
    }

    public function frenchMonthFilter(\DateTimeInterface $date): string
    {
        $monthNum = (int) $date->format('m');

        return self::MONTH_TRANSLATIONS[$monthNum] ?? '';
    }

    public function frenchDowFilter(string $dow): string
    {
        return self::DOW_TRANSLATIONS[strtolower($dow)] ?? $dow;
    }

    private function getFormatStyle(string $format): int
    {
        return match ($format) {
            'full' => \IntlDateFormatter::FULL,
            'long' => \IntlDateFormatter::LONG,
            'medium' => \IntlDateFormatter::MEDIUM,
            'short' => \IntlDateFormatter::SHORT,
            default => \IntlDateFormatter::FULL,
        };
    }
}

