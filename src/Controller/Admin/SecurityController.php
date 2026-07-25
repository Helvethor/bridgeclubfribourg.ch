<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    public function __construct(private readonly AuthenticationUtils $authenticationUtils)
    {
    }

    #[Route('/login', name: 'login')]
    public function login(): Response
    {
        if ($error = $this->authenticationUtils->getLastAuthenticationError()) {
            $this->addFlash('warning', 'Connexion impossible : ' . $error->getMessage());
        }

        return $this->render('admin/login.html.twig', [
            'lastUsername' => $this->authenticationUtils->getLastUsername(),
        ]);
    }
}
