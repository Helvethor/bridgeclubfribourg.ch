<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Page;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LessonsController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('/admin/lessons', name: 'admin_lessons')]
    public function lessons(Request $request): Response
    {
        $repo = $this->em->getRepository(Page::class);
        $page = $repo->findOneBy(['name' => 'Lessons']);

        if ($page === null) {
            $page = new Page();
            $page->setName('Lessons');
        }

        $form = $this->createFormBuilder($page)
            ->add('content', TextareaType::class, ['label' => 'Contenu'])
            ->add('send', SubmitType::class, ['label' => 'Envoyer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($page);
            $this->em->flush();
            $this->addFlash('success', 'La page a été enregistrée avec succès!');

            return $this->redirectToRoute('admin_lessons');
        }

        return $this->render('admin/lessons.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
