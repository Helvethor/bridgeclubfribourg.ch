<?php

declare(strict_types=1);

namespace App\Controller\Turnament;

use App\Entity\Person;
use App\Entity\Player;
use App\Turnament\Season;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StatsController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route(
        '/turnament/stats/{fsb}/{fromYear}/{dow}',
        name: 'turnament_stats',
        requirements: ['fromYear' => '\d+', 'dow' => '[a-z]+', 'fsb' => '-?\d+'],
        defaults: ['fromYear' => 'current', 'dow' => 'thursday']
    )]
    public function stats(string $fsb, string $fromYear, string $dow): Response
    {
        if ($fromYear === 'current') {
            $fromYear = (string) Season::getActual();
        }

        $fromYear   = (int) $fromYear;
        $toYear     = $fromYear + 1;
        $actualYear = Season::getActual();

        ['from' => $from, 'to' => $to] = Season::getFromToDates($fromYear, $toYear);

        $playerQb = $this->em->getRepository(Player::class)->createQueryBuilder('p');

        $players = $playerQb
            ->innerJoin('p.person', 'pe')
            ->innerJoin('p.turnament', 't')
            ->where($playerQb->expr()->between('t.date', ':from', ':to'))
            ->andWhere('t.dow = :dow')
            ->andWhere('pe.fsb = :fsb')
            ->setParameter('from', $from->format('Y-m-d'))
            ->setParameter('to', $to->format('Y-m-d'))
            ->setParameter('dow', $dow)
            ->setParameter('fsb', $fsb)
            ->orderBy('t.date', 'DESC')
            ->getQuery()
            ->getResult();

        $personRepo = $this->em->getRepository(Person::class);

        return $this->render('turnament/stats.html.twig', [
            'actualYear' => $actualYear,
            'fromYear'   => $fromYear,
            'toYear'     => $toYear,
            'dow'        => $dow,
            'persons'    => $personRepo->findAll(),
            'person'     => $personRepo->findOneBy(['fsb' => $fsb]),
            'players'    => $players,
        ]);
    }
}
