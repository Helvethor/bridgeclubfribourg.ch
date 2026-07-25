<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\PairRepository::class)]
#[ORM\Table(name: 'pair')]
class Pair
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\OneToMany(targetEntity: Board::class, mappedBy: 'pairNS')]
    private Collection $boardsNS;

    #[ORM\OneToMany(targetEntity: Board::class, mappedBy: 'pairEO')]
    private Collection $boardsEO;

    #[ORM\ManyToOne(targetEntity: Turnament::class, inversedBy: 'pairs')]
    #[ORM\JoinColumn(name: 'turnamentId', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?Turnament $turnament = null;

    #[ORM\ManyToMany(targetEntity: Player::class, inversedBy: 'pairs')]
    #[ORM\JoinTable(name: 'players_pairs')]
    private Collection $players;

    #[ORM\Column(name: 'no', type: 'string', length: 10)]
    private string $no = '';

    #[ORM\Column(name: 'rank', type: 'smallint')]
    private int $rank = 0;

    #[ORM\Column(name: 'mp', type: 'decimal', precision: 4, scale: 1)]
    private string $mp = '0';

    #[ORM\Column(name: 'pv', type: 'smallint')]
    private int $pv = 0;

    #[ORM\Column(name: 'result', type: 'decimal', precision: 4, scale: 2)]
    private string $result = '0';

    public function __construct()
    {
        $this->boardsNS = new ArrayCollection();
        $this->boardsEO = new ArrayCollection();
        $this->players = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setNo(string $no): self
    {
        $this->no = $no;

        return $this;
    }

    public function getNo(): string
    {
        return $this->no;
    }

    public function setRank(int $rank): self
    {
        $this->rank = $rank;

        return $this;
    }

    public function getRank(): int
    {
        return $this->rank;
    }

    public function setMp(string $mp): self
    {
        $this->mp = $mp;

        return $this;
    }

    public function getMp(): string
    {
        return $this->mp;
    }

    public function setPv(int $pv): self
    {
        $this->pv = $pv;

        return $this;
    }

    public function getPv(): int
    {
        return $this->pv;
    }

    public function setResult(string $result): self
    {
        $this->result = $result;

        return $this;
    }

    public function getResult(): string
    {
        return $this->result;
    }

    public function addBoardsNS(Board $boardsNS): self
    {
        $this->boardsNS[] = $boardsNS;

        return $this;
    }

    public function removeBoardsNS(Board $boardsNS): void
    {
        $this->boardsNS->removeElement($boardsNS);
    }

    public function getBoardsNS(): Collection
    {
        return $this->boardsNS;
    }

    public function addBoardsEO(Board $boardsEO): self
    {
        $this->boardsEO[] = $boardsEO;

        return $this;
    }

    public function removeBoardsEO(Board $boardsEO): void
    {
        $this->boardsEO->removeElement($boardsEO);
    }

    public function getBoardsEO(): Collection
    {
        return $this->boardsEO;
    }

    public function getBoards(): Collection
    {
        $boards = array_merge($this->boardsEO->toArray(), $this->boardsNS->toArray());
        usort($boards, static fn (Board $a, Board $b): int => $a->getNo() <=> $b->getNo());

        return new ArrayCollection($boards);
    }

    public function getConfrontations(): array
    {
        $boards = $this->getBoards();
        $confrontations = [];

        $lastOpponent = null;
        $opponentPair = null;
        $continuity = 1;
        $j = -1;

        foreach ($boards as $board) {
            if ($this === $board->getPairNS()) {
                $pairPoints = $board->getPointsNS();
                $opponentPair = $board->getPairEO();
            } else {
                $pairPoints = $board->getPointsEO();
                $opponentPair = $board->getPairNS();
            }

            if ($opponentPair !== $lastOpponent || $opponentPair === null) {
                $j += 1;
                $continuity = 1;
                $lastOpponent = $opponentPair;
            } else {
                $continuity += 1;
            }

            $confrontations[$j]['views'][] = [
                'board' => $board,
                'pairPoints' => $pairPoints,
                'opponentPair' => $opponentPair,
                'scoreNS' => $board->getScoreNS(),
                'scoreEO' => $board->getScoreEO(),
            ];

            $confrontations[$j]['continuity'] = $continuity;
            $average = $confrontations[$j]['average'] ?? 0;
            $confrontations[$j]['average'] = ($average * ($continuity - 1) + $pairPoints) / $continuity;
        }

        return $confrontations;
    }

    public function setTurnament(?Turnament $turnament = null): self
    {
        $this->turnament = $turnament;

        return $this;
    }

    public function getTurnament(): ?Turnament
    {
        return $this->turnament;
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

    public function getPlayersName(): string
    {
        $names = $this->players->map(static fn (Player $player): string => $player->getPerson()?->getFullName() ?? '');

        return implode(', ', array_filter($names->toArray()));
    }
}
