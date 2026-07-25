<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\RegistrationRepository::class)]
class Registration
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: RegistrationBoard::class, inversedBy: 'registrations')]
    private $registrationBoard;

    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    private $idx;

    #[ORM\Column(type: 'string')]
    private $leftPartner;

    #[ORM\Column(type: 'string', nullable: true)]
    private $rightPartner;

    #[ORM\Column(type: 'string', nullable: true)]
    private $comment;

    private $no = 0;

    public function getIdx(): ?int
    {
        return $this->idx;
    }

    public function setIdx(int $idx): self
    {
        $this->idx = $idx;

        return $this;
    }

    public function getLeftPartner(): ?string
    {
        return $this->leftPartner;
    }

    public function setLeftPartner(string $leftPartner): self
    {
        $this->leftPartner = $leftPartner;

        return $this;
    }

    public function getRightPartner(): ?string
    {
        return $this->rightPartner;
    }

    public function setRightPartner(?string $rightPartner): self
    {
        $this->rightPartner = $rightPartner;

        return $this;
    }

    public function getRegistrationBoard(): ?RegistrationBoard
    {
        return $this->registrationBoard;
    }

    public function setRegistrationBoard(?RegistrationBoard $registrationBoard): self
    {
        $this->registrationBoard = $registrationBoard;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getNo(): ?int
    {
        return $this->no;
    }

    public function setNo(int $no): self
    {
        $this->no = $no;

        return $this;
    }

    public function getFormPresentation(): ?string
    {
        if ($this->rightPartner == null)
            return sprintf('%d: %s', $this->no, $this->leftPartner);
        return sprintf('%d: %s & %s', $this->no, $this->leftPartner, $this->rightPartner);
    }
}
