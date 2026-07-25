<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\OnlineGame;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OnlineGameController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('/admin/online_game', name: 'admin_online_game')]
    public function index(Request $request): Response
    {
        $onlineGames = $this->em->getRepository(OnlineGame::class)->createQueryBuilder('g')
            ->orderBy('g.dateAndTime', 'ASC')
            ->getQuery()
            ->getResult();

        $form = $this->createFormBuilder()
            ->add('name', TextType::class, ['label' => 'Nom'])
            ->add('date', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
                'input' => 'datetime',
            ])
            ->add('time', TimeType::class, [
                'label' => 'Heure',
                'widget' => 'single_text',
                'input' => 'datetime',
            ])
            ->add('link', UrlType::class, ['label' => 'Lien'])
            ->add('send', SubmitType::class, ['label' => 'Envoyer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $name */
            $name = $form->get('name')->getData();
            /** @var \DateTimeInterface|null $date */
            $date = $form->get('date')->getData();
            /** @var \DateTimeInterface|null $time */
            $time = $form->get('time')->getData();
            /** @var string $link */
            $link = $form->get('link')->getData();

            if ($date === null || $time === null) {
                $this->addFlash('warning', 'La date et l\'heure sont obligatoires.');

                return $this->redirectToRoute('admin_online_game');
            }

            $dateAndTime = new \DateTime(
                $date->format('Y-m-d') . ' ' . $time->format('H:i:s')
            );

            $game = new OnlineGame();
            $game->setName($name);
            $game->setUntil($dateAndTime);
            $game->setLink($link);

            $this->em->persist($game);
            $this->em->flush();
            $this->addFlash('success', 'La partie en ligne a été enregistrée avec succès!');

            return $this->redirectToRoute('admin_online_game');
        }

        return $this->render('admin/online_game.html.twig', [
            'form' => $form->createView(),
            'onlineGames' => $onlineGames,
        ]);
    }

    #[Route('/admin/online_game/delete/{id}', name: 'admin_online_game_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $onlineGame = $this->em->getRepository(OnlineGame::class)->find($id);

        if ($onlineGame === null) {
            $this->addFlash('warning', 'Cette partie en ligne n\'existe pas.');
        } else {
            $this->em->remove($onlineGame);
            $this->em->flush();
            $this->addFlash('success', 'La partie en ligne a été supprimée avec succès.');
        }

        return $this->redirectToRoute('admin_online_game');
    }
}
