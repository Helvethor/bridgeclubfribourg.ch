<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Registration;
use App\Entity\RegistrationBoard;
use App\Form\ImmutableRegistrationBoardType;
use App\Form\RegistrationBoardType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationsController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('/admin/registrations', name: 'admin_registrations')]
    public function index(): Response
    {
        $repo = $this->em->getRepository(RegistrationBoard::class);

        $now = new \DateTime();
        $board = new RegistrationBoard();
        $board->setDate($now);
        $board->setTime($now);

        $boardForm = $this->createForm(RegistrationBoardType::class, $board, [
            'action' => $this->generateUrl('admin_registrations_create'),
        ]);

        return $this->render('admin/registrations.html.twig', [
            'boardForm' => $boardForm->createView(),
            'boards'    => $repo->findDateOrdered(),
        ]);
    }

    #[Route('/admin/registrations/create', name: 'admin_registrations_create')]
    public function create(Request $request): Response
    {
        $repo = $this->em->getRepository(RegistrationBoard::class);

        $boardForm = $this->createForm(RegistrationBoardType::class);
        $boardForm->handleRequest($request);

        if ($boardForm->isSubmitted() && $boardForm->isValid()) {
            /** @var RegistrationBoard $board */
            $board      = $boardForm->getData();
            $time       = $board->getTime();
            $date       = $board->getDate();
            if (!$date instanceof \DateTime) {
                throw new \LogicException('Expected DateTime, got ' . get_class($date));
            }
            $searchOnly = $board->getSearchOnly();
            $repeat     = (int) $boardForm['repeat']->getData();
            $successes  = 0;

            for ($i = 0; $i < $repeat; $i++) {
                $conflict = $repo->findOneBy(['date' => $date]);
                if ($conflict !== null) {
                    $this->addFlash('warning', 'Une inscription est déjà ouverte à la date du '
                        . $date->format('Y/m/d'));
                } else {
                    $this->em->persist($board);
                    $successes++;
                }

                $date = clone $date;
                $date->modify('+1 week');
                $board = new RegistrationBoard();
                $board->setDate($date);
                $board->setTime($time);
                $board->setSearchOnly($searchOnly);
            }
            $this->em->flush();

            if ($successes === 1) {
                $this->addFlash('success', 'Une inscription a été ouverte');
            } elseif ($successes > 1) {
                $this->addFlash('success', $successes . ' inscriptions ont été ouvertes');
            } else {
                $this->addFlash('danger', 'Aucune inscription n\'a été ouverte');
            }
        }

        return $this->redirectToRoute('admin_registrations');
    }

    #[Route('/admin/registrations/modify/{id}', name: 'admin_registrations_modify')]
    public function modify(Request $request, int $id): Response
    {
        $board = $this->em->getRepository(RegistrationBoard::class)->find($id);

        if ($board === null) {
            $this->addFlash('danger', 'L\'inscription demandée est introuvable');

            return $this->redirectToRoute('admin_registrations');
        }

        $boardForm = $this->createForm(ImmutableRegistrationBoardType::class, $board, [
            'action' => $this->generateUrl('admin_registrations_modify', ['id' => $board->getId()]),
        ]);
        $boardForm->handleRequest($request);

        if ($boardForm->isSubmitted() && $boardForm->isValid()) {
            $this->em->persist($boardForm->getData());
            $this->em->flush();
            $this->addFlash('success', 'L\'inscription a été modifiée');

            return $this->redirectToRoute('admin_registrations');
        }

        return $this->render('admin/registrations.modify.html.twig', [
            'form'  => $boardForm->createView(),
            'board' => $board,
        ]);
    }

    #[Route('/admin/registrations/delete/{id}', name: 'admin_registrations_delete')]
    public function delete(int $id): Response
    {
        $board = $this->em->getRepository(RegistrationBoard::class)->find($id);

        if ($board === null) {
            $this->addFlash('warning', 'Aucune inscription correspondante trouvée');
        } else {
            $this->em->remove($board);
            $this->em->flush();
            $this->addFlash('success', 'L\'inscription a bien été supprimée');
        }

        return $this->redirectToRoute('admin_registrations');
    }

    #[Route('/admin/registrations/delete/{id}/{idx}', name: 'admin_registrations_registration_delete')]
    public function deleteRegistration(int $id, int $idx): Response
    {
        $registration = $this->em->getRepository(Registration::class)->findOneByIdx($id, $idx);

        if ($registration === null) {
            $this->addFlash('warning', 'Aucune inscription correspondante trouvée');
        } else {
            $registration->getRegistrationBoard()->removeRegistration($registration);
            $this->em->remove($registration);
            $this->em->flush();
            $this->addFlash('success', 'L\'inscription a bien été supprimée');
        }

        return $this->redirectToRoute('admin_registrations_modify', ['id' => $id]);
    }

    #[Route('/admin/registrations/delete/{id}/snack/{name}', name: 'admin_registrations_snack_delete', methods: ['POST'])]
    public function deleteSnack(int $id, string $name): Response
    {
        $board = $this->em->getRepository(RegistrationBoard::class)->find($id);

        if ($board === null) {
            $this->addFlash('warning', 'Aucune inscription correspondante trouvée');

            return $this->redirectToRoute('admin_registrations');
        }

        $snacks = $board->getPeopleWhoBringSnack();
        $index = array_search($name, $snacks, true);

        if ($index === false) {
            $this->addFlash('warning', 'Aucun goûter correspondant trouvé');
        } else {
            unset($snacks[$index]);
            $board->setPeopleWhoBringSnack(array_values($snacks));
            $this->em->persist($board);
            $this->em->flush();
            $this->addFlash('success', 'Le goûter a bien été supprimé');
        }

        return $this->redirectToRoute('admin_registrations_modify', ['id' => $id]);
    }
}
