<?php

declare(strict_types=1);

namespace App\Controller\Turnament;

use App\Entity\Registration;
use App\Entity\RegistrationBoard;
use App\Form\CompleteRegistrationType;
use App\Form\FullRegistrationType;
use App\Form\HalfRegistrationType;
use App\Form\SnackContributionType;
use App\Turnament\DateUtils;
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

    #[Route(
        '/turnament/registrations/{year}/{month}/{day}',
        name: 'turnament_registrations',
        defaults: ['day' => 'current', 'month' => 'current', 'year' => 'current'],
        requirements: ['day' => '\d{2}', 'month' => '\d{2}', 'year' => '\d{4}']
    )]
    public function index(string $year, string $month, string $day): Response
    {
        ['year' => $year, 'month' => $month, 'day' => $day] = DateUtils::defaults(compact('year', 'month', 'day'));

        $repo      = $this->em->getRepository(RegistrationBoard::class);
        $board     = $repo->findOneByDate($year, $month, $day);
        $template  = 'turnament/registrations.html.twig';
        $fullForm  = null;
        $completeForm = null;
        $halfForm  = null;
        $snackForm = null;

        if ($board === null) {
            $date = \DateTime::createFromFormat('Y/m/d', "$year/$month/$day");
        } else {
            $date = $board->getDate();

            if (!$board->getSearchOnly()) {
                $registrations = [];
                foreach ($board->getRegistrations() as $registration) {
                    if ($registration->getRightPartner() === null) {
                        $registrations[$registration->getFormPresentation()] = $registration->getIdx();
                    }
                }

                $fullForm = $this->createForm(FullRegistrationType::class, null, [
                    'action' => $this->generateUrl('turnament_registrations_register_full',
                        compact('year', 'month', 'day')),
                ])->createView();

                $completeForm = $this->createForm(CompleteRegistrationType::class, null, [
                    'action' => $this->generateUrl('turnament_registrations_register_complete',
                        compact('year', 'month', 'day')),
                    'registrations' => $registrations,
                ])->createView();
            } else {
                $template = 'turnament/partners.html.twig';
            }

            $halfForm = $this->createForm(HalfRegistrationType::class, null, [
                'action' => $this->generateUrl('turnament_registrations_register_half',
                    compact('year', 'month', 'day')),
            ])->createView();

            $snackForm = $this->createForm(SnackContributionType::class, null, [
                'action' => $this->generateUrl('turnament_registrations_register_snack',
                    compact('year', 'month', 'day')),
            ])->createView();
        }

        return $this->render($template, compact('board', 'date', 'fullForm', 'halfForm', 'completeForm', 'snackForm'));
    }

    #[Route(
        '/turnament/registrations/{year}/{month}/{day}/register/full',
        name: 'turnament_registrations_register_full',
        defaults: ['day' => 'current', 'month' => 'current', 'year' => 'current'],
        requirements: ['day' => '\d{2}', 'month' => '\d{2}', 'year' => '\d{4}']
    )]
    public function registerFull(Request $request, string $year, string $month, string $day): Response
    {
        ['year' => $year, 'month' => $month, 'day' => $day] = DateUtils::defaults(compact('year', 'month', 'day'));

        $form = $this->createForm(FullRegistrationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $board = $this->em->getRepository(RegistrationBoard::class)->findOneByDate($year, $month, $day);

            if ($board === null) {
                $this->addFlash('warning', 'Les inscriptions pour cette date ne sont pas ouvertes');
            } elseif ($board->getSearchOnly()) {
                $this->addFlash('warning', 'Ce type d\'inscription n\'est pas autorisé');
            } else {
                /** @var Registration $registration */
                $registration = $form->getData();
                $registration->setIdx($this->nextIndex($board));
                $registration->setComment(null);
                $board->addRegistration($registration);
                $this->em->persist($board);
                $this->em->persist($registration);
                $this->em->flush();
                $this->addFlash('success', 'Votre inscription a été enregistrée');
            }
        }

        return $this->redirectToRoute('turnament_registrations', compact('year', 'month', 'day'));
    }

    #[Route(
        '/turnament/registrations/{year}/{month}/{day}/register/half',
        name: 'turnament_registrations_register_half',
        defaults: ['day' => 'current', 'month' => 'current', 'year' => 'current'],
        requirements: ['day' => '\d{2}', 'month' => '\d{2}', 'year' => '\d{4}']
    )]
    public function registerHalf(Request $request, string $year, string $month, string $day): Response
    {
        ['year' => $year, 'month' => $month, 'day' => $day] = DateUtils::defaults(compact('year', 'month', 'day'));

        $form = $this->createForm(HalfRegistrationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $board = $this->em->getRepository(RegistrationBoard::class)->findOneByDate($year, $month, $day);

            if ($board === null) {
                $this->addFlash('warning', 'Les inscriptions pour cette date ne sont pas ouvertes');
            } else {
                /** @var Registration $registration */
                $registration = $form->getData();
                $registration->setRightPartner(null);
                $registration->setIdx($this->nextIndex($board));
                $board->addRegistration($registration);
                $this->em->persist($board);
                $this->em->persist($registration);
                $this->em->flush();
                $this->addFlash('success', 'Votre inscription a été enregistrée');
            }
        }

        return $this->redirectToRoute('turnament_registrations', compact('year', 'month', 'day'));
    }

    #[Route(
        '/turnament/registrations/{year}/{month}/{day}/register/complete',
        name: 'turnament_registrations_register_complete',
        defaults: ['day' => 'current', 'month' => 'current', 'year' => 'current'],
        requirements: ['day' => '\d{2}', 'month' => '\d{2}', 'year' => '\d{4}']
    )]
    public function registerComplete(Request $request, string $year, string $month, string $day): Response
    {
        ['year' => $year, 'month' => $month, 'day' => $day] = DateUtils::defaults(compact('year', 'month', 'day'));

        $boardRepo = $this->em->getRepository(RegistrationBoard::class);
        $regRepo   = $this->em->getRepository(Registration::class);
        $board     = $boardRepo->findOneByDate($year, $month, $day);

        $registrations = [];
        if ($board !== null) {
            foreach ($board->getRegistrations() as $reg) {
                if ($reg->getRightPartner() === null) {
                    $registrations[$reg->getFormPresentation()] = $reg->getIdx();
                }
            }
        }

        $form = $this->createForm(CompleteRegistrationType::class, null, [
            'registrations' => $registrations,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Registration $pseudo */
            $pseudo       = $form->getData();
            $registration = $regRepo->findOneByIdx($board, $pseudo->getIdx());

            if ($board === null) {
                $this->addFlash('warning', 'Les inscriptions pour cette date ne sont pas ouvertes');
            } elseif ($board->getSearchOnly()) {
                $this->addFlash('warning', 'Ce type d\'inscription n\'est pas autorisé');
            } elseif ($registration === null) {
                $this->addFlash('warning', 'Le partenaire demandé est introuvable');
            } elseif ($registration->getRightPartner() !== null) {
                $this->addFlash('warning', 'Le partenaire demandé a déjà un partenaire');
            } else {
                $registration->setRightPartner($pseudo->getRightPartner());
                $this->em->persist($registration);
                $this->em->flush();
                $this->addFlash('success', 'Votre inscription a été enregistrée');
            }
        }

        return $this->redirectToRoute('turnament_registrations', compact('year', 'month', 'day'));
    }

    #[Route(
        '/turnament/registrations/{year}/{month}/{day}/register/snack',
        name: 'turnament_registrations_register_snack',
        defaults: ['day' => 'current', 'month' => 'current', 'year' => 'current'],
        requirements: ['day' => '\\d{2}', 'month' => '\\d{2}', 'year' => '\\d{4}']
    )]
    public function registerSnack(Request $request, string $year, string $month, string $day): Response
    {
        ['year' => $year, 'month' => $month, 'day' => $day] = DateUtils::defaults(compact('year', 'month', 'day'));

        $form = $this->createForm(SnackContributionType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $board = $this->em->getRepository(RegistrationBoard::class)->findOneByDate($year, $month, $day);

            if ($board === null) {
                $this->addFlash('warning', 'Les inscriptions pour cette date ne sont pas ouvertes');
            } else {
                $data = $form->getData();
                $name = trim((string) ($data['name'] ?? ''));

                if ($name === '') {
                    $this->addFlash('warning', 'Le nom est requis');
                } else {
                    $board->addToPeopleWhoBringSnack($name);
                    $this->em->persist($board);
                    $this->em->flush();
                    $this->addFlash('success', 'Votre contribution a été enregistrée');
                }
            }
        }

        return $this->redirectToRoute('turnament_registrations', compact('year', 'month', 'day'));
    }

    private function nextIndex(RegistrationBoard $board): int
    {
        $max = 0;
        foreach ($board->getRegistrations() as $registration) {
            $max = max($max, $registration->getIdx());
        }

        return $max + 1;
    }
}
