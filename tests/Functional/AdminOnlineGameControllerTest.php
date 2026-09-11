<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\OnlineGame;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\InMemoryUser;

class AdminOnlineGameControllerTest extends WebTestCase
{
    private function createAuthenticatedClient(): \Symfony\Bundle\FrameworkBundle\KernelBrowser
    {
        $client = static::createClient();
        $userProvider = static::getContainer()->get('security.user.provider.concrete.admin_user');
        $user = $userProvider->loadUserByIdentifier('admin');
        $client->loginUser($user);
        return $client;
    }

    public function testCreateOnlineGame(): void
    {
        $client = $this->createAuthenticatedClient();
        $crawler = $client->request('GET', '/admin/online_game');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Envoyer')->form([
            'form[name]' => 'Tournoi en Ligne Mardi',
            'form[date]' => '2026-06-15',
            'form[time]' => '20:00',
            'form[link]' => 'https://www.bridgebase.com',
        ]);

        $client->submit($form);
        $this->assertResponseRedirects('/admin/online_game');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $game = $em->getRepository(OnlineGame::class)->findOneBy(['name' => 'Tournoi en Ligne Mardi']);
        $this->assertNotNull($game);
        $this->assertSame('https://www.bridgebase.com', $game->getLink());
    }
}
