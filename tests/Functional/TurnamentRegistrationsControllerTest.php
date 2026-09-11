<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\RegistrationBoard;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TurnamentRegistrationsControllerTest extends WebTestCase
{
    public function testRegistrationsPageWithNoBoard(): void
    {
        $client = static::createClient();
        $client->request('GET', '/turnament/registrations/2026/06/15');

        $this->assertResponseIsSuccessful();
    }

    public function testRegistrationsPageWithBoard(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $board = new RegistrationBoard();
        $board->setDate(new \DateTime('2026-06-15'));
        $board->setTime(new \DateTime('2026-06-15 14:00:00'));
        $board->setSearchOnly(false);

        $em->persist($board);
        $em->flush();

        $crawler = $client->request('GET', '/turnament/registrations/2026/06/15');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }
}
