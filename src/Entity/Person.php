<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\PersonRepository::class)]
#[ORM\Table(name: 'person')]
class Person
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[ORM\OneToMany(targetEntity: Player::class, mappedBy: 'person')]
    private Collection $players;

    #[ORM\Column(name: 'fsb', type: 'string', length: 8, unique: true)]
    private string $fsb = '';

    #[ORM\Column(name: 'firstName', type: 'string', length: 255)]
    private string $firstName = '';

    #[ORM\Column(name: 'lastName', type: 'string', length: 255)]
    private string $lastName = '';

    public function __construct()
    {
        $this->players = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setFsb(string $fsb): self
    {
        $this->fsb = $fsb;

        return $this;
    }

    public function getFsb(): string
    {
        return $this->fsb;
    }

    public function getFsbNice(): string
    {
        if ((int) $this->fsb < 0) {
            return '∅';
        }
        return $this->fsb;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function addPair(Pair $pair): self
    {
        return $this;
    }

    public function removePair(Pair $pair): void
    {
    }

    public function getPairs(): Collection
    {
        return $this->players;
    }
}
