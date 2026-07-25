<?php

declare(strict_types=1);

namespace App\Controller\Turnament;

use App\Entity\Person;
use App\Turnament\Season;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PalmaresController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route(
        '/turnament/palmares/{fromYear}/{dow}',
        name: 'turnament_palmares',
        requirements: ['fromYear' => '\d+', 'dow' => '[a-z]+'],
        defaults: ['fromYear' => 'current', 'dow' => 'thursday']
    )]
    public function palmares(string $fromYear, string $dow): Response
    {
        if ($fromYear === 'current') {
            $fromYear = (string) Season::getActual();
        }

        $fromYear  = (int) $fromYear;
        $toYear    = $fromYear + 1;
        $actualYear = Season::getActual();

        ['from' => $from, 'to' => $to] = Season::getFromToDates($fromYear, $toYear);

        $qb = $this->em->getRepository(Person::class)->createQueryBuilder('p');

        $performances = $qb
            ->select('p person, sum(pl.pv) pv, count(t.id) tCount')
            ->innerJoin('p.players', 'pl')
            ->innerJoin('pl.turnament', 't')
            ->where($qb->expr()->between('t.date', ':from', ':to'))
            ->andWhere('t.dow = :dow')
            ->setParameter('from', $from->format('Y-m-d'))
            ->setParameter('to', $to->format('Y-m-d'))
            ->setParameter('dow', $dow)
            ->groupBy('p.id')
            ->orderBy('pv', 'DESC')
            ->addOrderBy('tCount', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('turnament/palmares.html.twig', [
            'performances' => $performances,
            'dow'          => $dow,
            'actualYear'   => $actualYear,
            'fromYear'     => $fromYear,
            'toYear'       => $toYear,
        ]);
    }
}
