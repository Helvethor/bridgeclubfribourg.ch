<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\News;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    public function testHomepageLoadsSuccessfully(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('#header, #title, .container-fluid');
    }

    public function testHomepageWithNewsContent(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $news = new News();
        $news->setTitle('Dernières Nouvelles du Club');
        $news->setContent('Tournoi exceptionnel ce week-end');
        $news->setDate(new \DateTime());
        $em->persist($news);
        $em->flush();

        $crawler = $client->request('GET', '/home');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Dernières Nouvelles du Club', (string) $client->getResponse()->getContent());
    }
}
