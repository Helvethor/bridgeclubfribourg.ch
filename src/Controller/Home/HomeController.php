<?php

declare(strict_types=1);

namespace App\Controller\Home;

use App\Entity\News;
use App\Entity\OnlineGame;
use App\Entity\Turnament;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('/', name: 'default')]
    #[Route('/home', name: 'homepage')]
    public function home(): Response
    {
        $now = new \DateTimeImmutable('now');
        $onlineGames = $this->em->getRepository(OnlineGame::class)->createQueryBuilder('g')
            ->andWhere('g.dateAndTime >= :now')
            ->setParameter('now', $now)
            ->orderBy('g.dateAndTime', 'ASC')
            ->getQuery()
            ->getResult();

        $turnaments = $this->em->getRepository(Turnament::class)->findBy(
            [],
            ['date' => 'DESC'],
            4
        );

        $newz = $this->em->getRepository(News::class)->findBy(
            [],
            ['id' => 'DESC'],
            4
        );

        return $this->render('home/home.html.twig', [
            'onlineGames' => $onlineGames,
            'turnaments'  => $turnaments,
            'newz'        => $newz,
        ]);
    }
}
