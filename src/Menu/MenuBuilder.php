<?php

declare(strict_types=1);

namespace App\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class MenuBuilder
{
    public function __construct(
        private readonly FactoryInterface $factory,
        private readonly AuthorizationCheckerInterface $security,
    ) {
    }

    public function mainMenu(array $options): ItemInterface
    {
        $menu = $this->factory->createItem('root');

        $menu->addChild('Accueil', ['route' => 'homepage']);
        $menu->addChild('Tournois', ['route' => 'turnament_calendar']);
        $menu->addChild('Palmarès', ['route' => 'turnament_palmares']);
        $menu->addChild('Cours', ['route' => 'lessons']);

        if ($this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            $menu->addChild('Déconnexion', ['route' => 'logout']);
        } else {
            $menu->addChild('Connexion', ['route' => 'login']);
        }

        return $menu;
    }

    public function adminMenu(array $options): ?ItemInterface
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            return null;
        }

        $menu = $this->factory->createItem('root');

        $menu->addChild('Gestion :');
        $menu->addChild('Tournois', ['route' => 'admin_turnament']);
        $menu->addChild('Inscriptions', ['route' => 'admin_registrations']);
        $menu->addChild('News', ['route' => 'admin_news']);
        $menu->addChild('Cours', ['route' => 'admin_lessons']);
        $menu->addChild('Jeu en ligne', ['route' => 'admin_online_game']);

        return $menu;
    }
}
