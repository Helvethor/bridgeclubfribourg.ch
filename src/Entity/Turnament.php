<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\TurnamentRepository::class)]
#[ORM\Table(name: 'turnament')]
class Turnament
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(name: 'isExternal', type: 'boolean', options: ['default' => false])]
    private bool $isExternal = false;

    #[ORM\Column(name: 'isIndividual', type: 'boolean', options: ['default' => false])]
    private bool $isIndividual = false;

    #[ORM\Column(name: 'externalLink', type: 'string', nullable: true)]
    private ?string $externalLink = null;

    #[ORM\OneToMany(targetEntity: Board::class, mappedBy: 'turnament')]
    private Collection $boards;

    #[ORM\OneToMany(targetEntity: Pair::class, mappedBy: 'turnament')]
    private Collection $pairs;

    #[ORM\OneToMany(targetEntity: Player::class, mappedBy: 'turnament')]
    private Collection $players;

    #[ORM\Column(name: 'type', type: 'string', length: 32)]
    private string $type = '';

    #[ORM\Column(name: 'date', type: 'date', unique: true)]
    private ?DateTimeInterface $date = null;

    #[ORM\Column(name: 'dow', type: 'string')]
    private string $dow = '';

    #[ORM\Column(name: 'files', type: 'json')]
    private array $files = [];

    public function __construct()
    {
        $this->boards = new ArrayCollection();
        $this->pairs = new ArrayCollection();
        $this->players = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setDate(DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getDate(): ?DateTimeInterface
    {
        return $this->date;
    }

    public function setFiles(array $files): self
    {
        $this->files = $files;

        return $this;
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    public function getFilesUrl(): array
    {
        $filesUrl = [];

        foreach ($this->files as $key => $file) {
            $filesUrl[$key] = preg_replace('/.*\/(web|public)\/(.*)/', '/\2', (string) $file);
        }

        return $filesUrl;
    }

    public function addCustomFile(string $file): self
    {
        $this->files['custom'] = $file;

        return $this;
    }

    public function getCustomFileUrl(): string|false
    {
        if (isset($this->files['custom'])) {
            $filesUrl = $this->getFilesUrl();

            return $filesUrl['custom'] ?? false;
        }

        return false;
    }

    public function getXLSFileUrl(): string|false
    {
        if (isset($this->files['xlsFile'])) {
            $filesUrl = $this->getFilesUrl();

            return $filesUrl['xlsFile'] ?? false;
        }

        return false;
    }

    public function addBoard(Board $board): self
    {
        $this->boards[] = $board;

        return $this;
    }

    public function removeBoard(Board $board): void
    {
        $this->boards->removeElement($board);
    }

    public function getBoards(): Collection
    {
        return $this->boards;
    }

    public function getBoardsIndexes(): array
    {
        $boardsIndexes = [];
        $lastIndex = -1;

        foreach ($this->boards as $board) {
            $index = $board->getNo();
            if ($index !== $lastIndex) {
                $boardsIndexes[$index] = $index;
                $lastIndex = $index;
            }
        }

        return $boardsIndexes;
    }

    public function addPair(Pair $pair): self
    {
        $this->pairs[] = $pair;

        return $this;
    }

    public function removePair(Pair $pair): void
    {
        $this->pairs->removeElement($pair);
    }

    public function getPairs(): Collection
    {
        return $this->pairs;
    }

    public function getSortedPairs(): Collection
    {
        $pairs = $this->pairs->toArray();
        usort($pairs, static fn (Pair $a, Pair $b): int => $a->getNo() <=> $b->getNo());

        return new ArrayCollection($pairs);
    }

    public function getSortedPlayers(): Collection
    {
        $players = $this->players->toArray();
        usort($players, static fn (Player $a, Player $b): int => $a->getNo() <=> $b->getNo());

        return new ArrayCollection($players);
    }

    public function getFormPresentation(): string
    {
        $fmt = \IntlDateFormatter::create('fr_CH', \IntlDateFormatter::LONG, \IntlDateFormatter::NONE);
        $displayDate = $this->date instanceof DateTimeInterface ? $fmt->format($this->date) : '';

        return sprintf('%s | %s', $this->id, $displayDate);
    }

    public function setDow(string $dow): self
    {
        $this->dow = $dow;

        return $this;
    }

    public function getDow(): string
    {
        return $this->dow;
    }

    public function getIsExternal(): bool
    {
        return $this->isExternal;
    }

    public function setIsExternal(bool $isExternal): self
    {
        $this->isExternal = $isExternal;

        return $this;
    }

    public function getIsIndividual(): bool
    {
        return $this->isIndividual;
    }

    public function setIsIndividual(bool $isIndividual): self
    {
        $this->isIndividual = $isIndividual;

        return $this;
    }

    public function getExternalLink(): ?string
    {
        return $this->externalLink;
    }

    public function setExternalLink(?string $externalLink): self
    {
        $this->externalLink = $externalLink;

        return $this;
    }

    public function addPlayer(Player $player): self
    {
        $this->players[] = $player;

        return $this;
    }

    public function removePlayer(Player $player): void
    {
        $this->players->removeElement($player);
    }

    public function getPlayers(): Collection
    {
        return $this->players;
    }
}
