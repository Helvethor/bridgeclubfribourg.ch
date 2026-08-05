<?php

declare(strict_types=1);

namespace App\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

class MenuBuilder
{
    private int $position = 3;

    private const SUIT_STYLES = [
        ['class' => 'black', 'symbol' => '♠'],
        ['class' => 'red', 'symbol' => '♥'],
        ['class' => 'green', 'symbol' => '♣'],
        ['class' => 'yellow', 'symbol' => '♦'],
    ];

    public function __construct(
        private readonly FactoryInterface $factory,
        private readonly AuthorizationCheckerInterface $security,
        private readonly Environment $twig,
    ) {
    }

    public function mainMenu(array $options): ItemInterface
    {
        $menu = $this->factory->createItem('root');
        $this->position = 3;

        if ($this->security->isGranted('ROLE_ADMIN')) {
            $this->addMenuChild($menu, 'Public :', []);
        }

        $this->addMenuChild($menu, 'Accueil', ['route' => 'homepage'], $this->position);
        $this->addMenuChild($menu, 'Tournois', ['route' => 'turnament_calendar'], $this->position);
        $this->addMenuChild($menu, 'Palmarès', ['route' => 'turnament_palmares'], $this->position);
        $this->addMenuChild($menu, 'Cours', ['route' => 'lessons'], $this->position);

        if ($this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            $this->addMenuChild($menu, 'Déconnexion', ['route' => 'logout'], $this->position);
        } else {
            $this->addMenuChild($menu, 'Connexion', ['route' => 'login'], $this->position);
        }

        return $menu;
    }

    public function adminMenu(array $options): ?ItemInterface
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            return null;
        }

        $menu = $this->factory->createItem('root');

        $this->addMenuChild($menu, 'Gestion :', []);
        $this->addMenuChild($menu, 'Tournois', ['route' => 'admin_turnament'], $this->position);
        $this->addMenuChild($menu, 'Inscriptions', ['route' => 'admin_registrations'], $this->position);
        $this->addMenuChild($menu, 'News', ['route' => 'admin_news'], $this->position);
        $this->addMenuChild($menu, 'Cours', ['route' => 'admin_lessons'], $this->position);
        $this->addMenuChild($menu, 'Jeu en ligne', ['route' => 'admin_online_game'], $this->position);

        return $menu;
    }

    private function addMenuChild(ItemInterface $menu, string $label, array $options = [], int &$position = 1): ItemInterface
    {
        $isLink = !empty($options['route']) || !empty($options['uri']);

        if ($isLink) {
            $style = $this->suitStyleForPosition($position);
            $label = $this->twig->render(
                'menu/_suit_label.html.twig',
                [
                    'class' => $style['class'],
                    'symbol' => $style['symbol'],
                    'label' => $label,
                ],
            );
        }

        $child = $menu->addChild($label, $options);

        if ($isLink) {
            $existingClasses = (string) $child->getLinkAttribute('class');
            $classes = array_filter([
                'btn',
                'btn-outline-' . $style['class'],
                $existingClasses,
            ]);

            $child->setLinkAttribute('class', trim(implode(' ', $classes)));
            $child->setExtra('safe_label', true);
        }

        $position++;

        return $child;
    }

    /**
     * @return array{class: string, symbol: string}
     */
    private function suitStyleForPosition(int $position): array
    {
        $index = ($position - 1) % count(self::SUIT_STYLES);

        return self::SUIT_STYLES[$index];
    }
}
