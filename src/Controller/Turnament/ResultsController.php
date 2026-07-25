<?php

declare(strict_types=1);

namespace App\Controller\Turnament;

use App\Entity\Board;
use App\Entity\Pair;
use App\Entity\Player;
use App\Entity\Turnament;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ResultsController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route(
        '/turnament/results/{year}/{month}/{day}',
        name: 'turnament_result',
        requirements: ['day' => '\d{2}', 'month' => '\d{2}', 'year' => '\d{4}']
    )]
    public function results(string $day, string $month, string $year): Response
    {
        $date      = \DateTime::createFromFormat('d/m/Y H:i:s', "$day/$month/$year 00:00:00");
        $turnament = $this->em->getRepository(Turnament::class)->findOneBy(['date' => $date]);

        return $this->render('turnament/results.html.twig', [
            'turnament' => $turnament,
        ]);
    }

    #[Route(
        '/turnament/results/{year}/{month}/{day}/pair/{no}',
        name: 'turnament_pair',
        requirements: ['day' => '\d{2}', 'month' => '\d{2}', 'year' => '\d{4}', 'no' => '\d+']
    )]
    public function pair(string $day, string $month, string $year, int $no): Response
    {
        $date = \DateTime::createFromFormat('d/m/Y H:i:s', "$day/$month/$year 00:00:00");
        $qb   = $this->em->createQueryBuilder();

        $pairs = $qb->select('p')
            ->from(Pair::class, 'p')
            ->join('p.turnament', 't')
            ->where('p.no = :no')
            ->andWhere($qb->expr()->eq('t.date', ':date'))
            ->setParameter('date', $date)
            ->setParameter('no', $no)
            ->getQuery()
            ->getResult();

        return $this->render('turnament/pair.html.twig', [
            'pair' => $pairs[0] ?? null,
        ]);
    }

    #[Route(
        '/turnament/results/{year}/{month}/{day}/player/{no}',
        name: 'turnament_player',
        requirements: ['day' => '\d{2}', 'month' => '\d{2}', 'year' => '\d{4}', 'no' => '\d+']
    )]
    public function player(string $day, string $month, string $year, int $no): Response
    {
        $date = \DateTime::createFromFormat('d/m/Y H:i:s', "$day/$month/$year 00:00:00");
        $qb   = $this->em->createQueryBuilder();

        $players = $qb->select('p')
            ->from(Player::class, 'p')
            ->join('p.turnament', 't')
            ->where('p.no = :no')
            ->andWhere($qb->expr()->eq('t.date', ':date'))
            ->setParameter('date', $date)
            ->setParameter('no', $no)
            ->getQuery()
            ->getResult();

        return $this->render('turnament/player.html.twig', [
            'player' => $players[0] ?? null,
        ]);
    }

    #[Route(
        '/turnament/results/{year}/{month}/{day}/board/{no}',
        name: 'turnament_board',
        requirements: ['day' => '\d{2}', 'month' => '\d{2}', 'year' => '\d{4}', 'no' => '\d+']
    )]
    public function board(string $day, string $month, string $year, int $no): Response
    {
        $date = \DateTime::createFromFormat('d/m/Y H:i:s', "$day/$month/$year 00:00:00");
        $qb   = $this->em->createQueryBuilder();

        $boards = $qb->select('b')
            ->from(Board::class, 'b')
            ->join('b.turnament', 't')
            ->where('b.no = :no')
            ->andWhere($qb->expr()->eq('t.date', ':date'))
            ->setParameter('date', $date)
            ->setParameter('no', $no)
            ->getQuery()
            ->getResult();

        return $this->render('turnament/board.html.twig', [
            'boards' => $boards,
        ]);
    }
}
