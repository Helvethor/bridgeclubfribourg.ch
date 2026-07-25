<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\OnlineGameRepository::class)]
#[ORM\Table(name: 'online_game')]
class OnlineGame
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(name: 'name', type: 'string', length: 64)]
    private string $name = '';

    #[ORM\Column(name: 'until', type: 'datetime')]
    private DateTimeInterface $dateAndTime;

    #[ORM\Column(name: 'link', type: 'string')]
    private string $link = '';

    public function getId(): int
    {
        return $this->id;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setDateAndTime(DateTimeInterface $dateAndTime): self
    {
        $this->dateAndTime = $dateAndTime;

        return $this;
    }

    public function getDateAndTime(): DateTimeInterface
    {
        return $this->dateAndTime;
    }

    public function setUntil(DateTimeInterface $until): self
    {
        return $this->setDateAndTime($until);
    }

    public function getUntil(): DateTimeInterface
    {
        return $this->getDateAndTime();
    }

    public function setLink(string $link): self
    {
        $this->link = $link;

        return $this;
    }

    public function getLink(): string
    {
        return $this->link;
    }
}
