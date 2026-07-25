<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\News;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NewsController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('/admin/news/{id}', name: 'admin_news', defaults: ['id' => 'new'], requirements: ['id' => '\d+'])]
    public function news(Request $request, string $id): Response
    {
        $repo = $this->em->getRepository(News::class);

        if ($id === 'new') {
            $news = new News();
            $news->setDate(new \DateTime());
        } else {
            $news = $repo->find((int) $id);
            if ($news === null) {
                $news = new News();
                $this->addFlash('warning', "Aucune news n'existe pour cet ID.");
            }
        }

        $form = $this->createFormBuilder($news)
            ->add('title', TextType::class, ['label' => 'Titre'])
            ->add('content', TextareaType::class, ['label' => 'Contenu'])
            ->add('date', DateType::class, ['label' => 'Date'])
            ->add('send', SubmitType::class, ['label' => 'Envoyer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($news);
            $this->em->flush();
            $this->addFlash('success', 'La news a été enregistrée avec succès!');

            return $this->redirectToRoute('admin_news');
        }

        return $this->render('admin/news.html.twig', [
            'form' => $form->createView(),
            'newz' => $repo->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/admin/news/delete/{id}', name: 'admin_news_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id): Response
    {
        $news = $this->em->getRepository(News::class)->find($id);

        if ($news === null) {
            $this->addFlash('warning', 'Cette news n\'existe pas.');
        } else {
            $this->em->remove($news);
            $this->em->flush();
            $this->addFlash('success', 'La news a été supprimée avec succès.');
        }

        return $this->redirectToRoute('admin_news');
    }
}
