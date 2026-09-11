<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\RegistrationBoard;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\InMemoryUser;

class AdminRegistrationsControllerTest extends WebTestCase
{
    private function createAuthenticatedClient(): \Symfony\Bundle\FrameworkBundle\KernelBrowser
    {
        $client = static::createClient();
        $userProvider = static::getContainer()->get('security.user.provider.concrete.admin_user');
        $user = $userProvider->loadUserByIdentifier('admin');
        $client->loginUser($user);
        return $client;
    }

    public function testRegistrationsIndexPage(): void
    {
        $client = $this->createAuthenticatedClient();
        $crawler = $client->request('GET', '/admin/registrations');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testCreateRegistrationBoard(): void
    {
        $client = $this->createAuthenticatedClient();
        $crawler = $client->request('GET', '/admin/registrations');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Enregistrer')->form([
            'registration_board[date]' => '2027-01-10',
            'registration_board[time]' => '14:00',
            'registration_board[searchOnly]' => '0',
            'registration_board[repeat]' => '1',
        ]);

        $client->submit($form);
        $this->assertResponseRedirects('/admin/registrations');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $board = $em->getRepository(RegistrationBoard::class)->findOneByDate('2027', '01', '10');
        $this->assertNotNull($board);
    }
}
