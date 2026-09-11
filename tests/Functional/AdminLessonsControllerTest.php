<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Page;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\InMemoryUser;

class AdminLessonsControllerTest extends WebTestCase
{
    private function createAuthenticatedClient(): \Symfony\Bundle\FrameworkBundle\KernelBrowser
    {
        $client = static::createClient();
        $userProvider = static::getContainer()->get('security.user.provider.concrete.admin_user');
        $user = $userProvider->loadUserByIdentifier('admin');
        $client->loginUser($user);
        return $client;
    }

    public function testUpdateLessonsPage(): void
    {
        $client = $this->createAuthenticatedClient();
        $crawler = $client->request('GET', '/admin/lessons');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Envoyer')->form([
            'form[content]' => '<h2>Programme des cours 2026</h2><p>Tous les mercredis à 14h.</p>',
        ]);

        $client->submit($form);
        $this->assertResponseRedirects('/admin/lessons');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $page = $em->getRepository(Page::class)->findOneBy(['name' => 'Lessons']);
        $this->assertNotNull($page);
        $this->assertStringContainsString('Programme des cours 2026', $page->getContent());

        // Now verify it renders on the public page
        $client->request('GET', '/lessons');
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Programme des cours 2026', (string) $client->getResponse()->getContent());
    }
}
