<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\News;
use App\Entity\OnlineGame;
use App\Entity\Page;
use App\Entity\Person;
use App\Entity\Registration;
use App\Entity\RegistrationBoard;
use App\Entity\Turnament;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class EntityMappingTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testPersistAndRetrieveNews(): void
    {
        $news = new News();
        $news->setTitle('Tournoi de Printemps');
        $news->setContent('<p>Bienvenue au tournoi!</p>');
        $news->setDate(new \DateTime('2026-04-10'));

        $this->em->persist($news);
        $this->em->flush();
        $this->em->clear();

        $retrieved = $this->em->getRepository(News::class)->find($news->getId());
        $this->assertNotNull($retrieved);
        $this->assertSame('Tournoi de Printemps', $retrieved->getTitle());
        $this->assertSame('<p>Bienvenue au tournoi!</p>', $retrieved->getContent());
    }

    public function testPersistAndRetrievePage(): void
    {
        $page = new Page();
        $page->setName('TestPage');
        $page->setContent('Contenu test');

        $this->em->persist($page);
        $this->em->flush();
        $this->em->clear();

        $retrieved = $this->em->getRepository(Page::class)->findOneBy(['name' => 'TestPage']);
        $this->assertNotNull($retrieved);
        $this->assertSame('Contenu test', $retrieved->getContent());
    }

    public function testPersistAndRetrieveOnlineGame(): void
    {
        $game = new OnlineGame();
        $game->setName('BBO Mardi Soir');
        $game->setUntil(new \DateTime('2026-05-12 20:00:00'));
        $game->setLink('https://bridgebase.com');

        $this->em->persist($game);
        $this->em->flush();
        $this->em->clear();

        $retrieved = $this->em->getRepository(OnlineGame::class)->find($game->getId());
        $this->assertNotNull($retrieved);
        $this->assertSame('BBO Mardi Soir', $retrieved->getName());
        $this->assertSame('https://bridgebase.com', $retrieved->getLink());
    }

    public function testPersistAndRetrieveRegistrationBoardWithRegistrations(): void
    {
        $board = new RegistrationBoard();
        $board->setDate(new \DateTime('2028-11-20'));
        $board->setTime(new \DateTime('2028-11-20 14:00:00'));
        $board->setSearchOnly(false);

        $reg = new Registration();
        $reg->setLeftPartner('Alice');
        $reg->setRightPartner('Bob');
        $reg->setRegistrationBoard($board);
        $reg->setIdx(1);

        $board->addRegistration($reg);

        $this->em->persist($board);
        $this->em->flush();
        $this->em->clear();

        $retrieved = $this->em->getRepository(RegistrationBoard::class)->findOneByDate('2028', '11', '20');
        $this->assertNotNull($retrieved);
        $this->assertCount(1, $retrieved->getRegistrations());
        $this->assertSame('Alice', $retrieved->getRegistrations()->first()->getLeftPartner());
        $this->assertSame('Bob', $retrieved->getRegistrations()->first()->getRightPartner());
    }
}
