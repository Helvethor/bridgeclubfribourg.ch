<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\News;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\InMemoryUser;

class AdminNewsControllerTest extends WebTestCase
{
    private function createAuthenticatedClient(): \Symfony\Bundle\FrameworkBundle\KernelBrowser
    {
        $client = static::createClient();
        $userProvider = static::getContainer()->get('security.user.provider.concrete.admin_user');
        $user = $userProvider->loadUserByIdentifier('admin');
        $client->loginUser($user);
        return $client;
    }

    public function testCreateNews(): void
    {
        $client = $this->createAuthenticatedClient();
        $crawler = $client->request('GET', '/admin/news');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Envoyer')->form([
            'form[title]' => 'Tournoi Spécial Débutants',
            'form[content]' => 'Tous les détails sont ici.',
            'form[date]' => '2026-04-25',
        ]);

        $client->submit($form);
        $this->assertResponseRedirects('/admin/news');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $news = $em->getRepository(News::class)->findOneBy(['title' => 'Tournoi Spécial Débutants']);
        $this->assertNotNull($news);
        $this->assertSame('Tous les détails sont ici.', $news->getContent());
    }

    public function testDeleteNews(): void
    {
        $client = $this->createAuthenticatedClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $news = new News();
        $news->setTitle('News to be deleted');
        $news->setContent('Content');
        $news->setDate(new \DateTime());
        $em->persist($news);
        $em->flush();

        $newsId = $news->getId();
        $client->request('POST', '/admin/news/delete/' . $newsId);

        $this->assertResponseRedirects('/admin/news');

        $em->clear();
        $deleted = $em->getRepository(News::class)->find($newsId);
        $this->assertNull($deleted);
    }
}
