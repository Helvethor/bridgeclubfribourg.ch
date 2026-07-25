<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Turnament;
use App\Turnament\Creator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TurnamentController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly string $turnamentDir
    ) {
    }

    #[Route('/admin/turnament', name: 'admin_turnament')]
    public function index(Request $request): Response
    {
        $turnaments = $this->em->getRepository(Turnament::class)->findBy([], ['date' => 'DESC']);
        return $this->render('admin/turnament.html.twig', [
            'turnamentFileForm'    => $this->buildTurnamentFileForm()->createView(),
            'externalLinkForm'     => $this->buildExternalLinkForm()->createView(),
            'turnamentBoardFileForm' => $this->buildBoardFileForm($turnaments)->createView(),
            'turnaments'           => $turnaments,
        ]);
    }

    #[Route('/admin/turnament/create', name: 'admin_turnament_create', methods: ['POST'])]
    public function create(Creator $creator, Request $request): Response
    {
        $form = $this->buildTurnamentFileForm();
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $uploadedFile = $form->get('turnamentFile')->getData();
            $result = $creator->createFromFile($uploadedFile);
            $this->addFlash($result['status'], $result['message']);
        }

        return $this->redirectToRoute('admin_turnament');
    }

    #[Route('/admin/turnament/delete/{id}', name: 'admin_turnament_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $turnament = $this->em->getRepository(Turnament::class)->find($id);

        if ($turnament === null) {
            $this->addFlash('warning', 'Ce tournois n\'existe pas.');
        } else {
            $this->em->remove($turnament);
            $this->em->flush();
            $this->addFlash('success', 'Le tournois a été supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_turnament');
    }

    #[Route('/admin/turnament/custom', name: 'admin_turnament_custom', methods: ['POST'])]
    public function custom(Request $request): Response
    {
        $turnaments = $this->em->getRepository(Turnament::class)->findBy([], ['date' => 'DESC']);
        $form = $this->buildBoardFileForm($turnaments);
        $form->handleRequest($request);

        $turnament  = $form->get('turnament')->getData();
        $boardFile  = $form->get('turnamentBoardFile')->getData();

        $uploadDir = $this->turnamentDir . '/custom/';
        $fileBase      = $turnament->getDate()->format('d.m.Y');
        $fileExtension = preg_replace('/.*(\.\[a-zA-Z\]+)$/', '\1', (string) $boardFile->getClientOriginalName());

        $boardFile->move($uploadDir, $fileBase . $fileExtension);
        $turnament->addCustomFile($uploadDir . $fileBase . $fileExtension);
        $this->em->persist($turnament);
        $this->em->flush();

        $this->addFlash('success', 'Le fichier a été ajouté avec succès!');

        return $this->redirectToRoute('admin_turnament');
    }

    #[Route('/admin/turnament/external', name: 'admin_turnament_external', methods: ['POST'])]
    public function external(Creator $creator, Request $request): Response
    {
        $form = $this->buildExternalLinkForm();
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $result = $creator->createFromLink(
                $form->get('externalLink')->getData(),
                $form->get('date')->getData()
            );
            $this->addFlash($result['status'], $result['message']);
        }

        return $this->redirectToRoute('admin_turnament');
    }

    private function buildTurnamentFileForm(): FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_turnament_create'))
            ->add('turnamentFile', FileType::class, ['label' => false])
            ->add('submit', SubmitType::class, ['label' => 'Envoyer'])
            ->getForm();
    }

    private function buildExternalLinkForm(): FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_turnament_external'))
            ->add('date', DateType::class, ['label' => 'Date du tournois'])
            ->add('externalLink', UrlType::class, ['label' => 'URL des résultats'])
            ->add('submit', SubmitType::class, ['label' => 'Envoyer'])
            ->getForm();
    }

    private function buildBoardFileForm(array $turnaments): FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_turnament_custom'))
            ->add('turnament', ChoiceType::class, [
                'choices'       => $turnaments,
                'choice_label'  => fn (Turnament $t): string => $t->getFormPresentation(),
            ])
            ->add('turnamentBoardFile', FileType::class, ['label' => false])
            ->add('submit', SubmitType::class, ['label' => 'Envoyer'])
            ->getForm();
    }
}
