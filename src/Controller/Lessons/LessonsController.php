<?php

declare(strict_types=1);

namespace App\Controller\Lessons;

use App\Entity\Page;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LessonsController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('/lessons', name: 'lessons')]
    public function lessons(): Response
    {
        $page = $this->em->getRepository(Page::class)->findOneBy(['name' => 'Lessons']);

        return $this->render('page/page.html.twig', [
            'page'  => $page,
            'title' => 'Cours',
        ]);
    }
}
