<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Person;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PalmaresAndStatsTest extends WebTestCase
{
    public function testPalmaresPageLoads(): void
    {
        $client = static::createClient();
        $client->request('GET', '/turnament/palmares/2025/thursday');

        $this->assertResponseIsSuccessful();
    }

    public function testStatsPageLoads(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $person = new Person();
        $person->setFsb('99999');
        $person->setFirstname('Jean');
        $person->setLastname('Dupont');
        $em->persist($person);
        $em->flush();

        $client->request('GET', '/turnament/stats/99999/2025/thursday');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Statistiques de Jean Dupont', (string) $client->getResponse()->getContent());
    }
}
