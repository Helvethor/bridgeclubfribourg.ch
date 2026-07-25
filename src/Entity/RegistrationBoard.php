<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\RegistrationBoardRepository::class)]
class RegistrationBoard
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\Column(type: 'date', unique: true)]
    private $date;

    #[ORM\Column(type: 'time')]
    private $time;

    #[ORM\OneToMany(targetEntity: Registration::class, mappedBy: 'registrationBoard', cascade: ['ALL'], indexBy: 'idx')]
    private $registrations;

    #[ORM\Column(type: 'boolean')]
    private $searchOnly;

    #[ORM\Column(type: 'json')]
    private $peopleWhoBringSnack;

    public function __construct()
    {
        $this->registrations = new ArrayCollection();
        $this->peopleWhoBringSnack = [];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getTime(): ?\DateTimeInterface
    {
        return $this->time;
    }

    public function getSearchOnly()
    {
        return $this->searchOnly;
    }

    public function setTime(\DateTimeInterface $time): self
    {
        $this->time = $time;

        return $this;
    }

    /**
     * @return Collection|Registration[]
     */
    public function getRegistrations(): Collection
    {
        $no = 1;
        foreach ($this->registrations as $registration) {
            $registration->setNo($no);
            $no++;
        }
        return $this->registrations;
    }

    public function addRegistration(Registration $registration): self
    {
        if (!$this->registrations->contains($registration)) {
            $this->registrations[] = $registration;
            $registration->setRegistrationBoard($this);
        }

        return $this;
    }

    public function removeRegistration(Registration $registration): self
    {
        if ($this->registrations->contains($registration)) {
            $this->registrations->removeElement($registration);
            // set the owning side to null (unless already changed)
            if ($registration->getRegistrationBoard() === $this) {
                $registration->setRegistrationBoard(null);
            }
        }

        return $this;
    }

    public function setSearchOnly(bool $searchOnly): self
    {
        $this->searchOnly = $searchOnly;

        return $this;
    }

    public function getPeopleWhoBringSnack(): array
    {
        return $this->peopleWhoBringSnack;
    }

    public function setPeopleWhoBringSnack(array $peopleWhoBringSnack): self
    {
        $this->peopleWhoBringSnack = $peopleWhoBringSnack;

        return $this;
    }

    public function addToPeopleWhoBringSnack(string $person): self
    {
        $this->peopleWhoBringSnack[] = $person;

        return $this;
    }
}
