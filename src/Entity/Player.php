<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\PlayerRepository::class)]
#[ORM\Table(name: 'player')]
class Player
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[ORM\Column(name: 'no', type: 'integer')]
    private int $no = 0;

    #[ORM\ManyToMany(targetEntity: Pair::class, mappedBy: 'players')]
    private Collection $pairs;

    #[ORM\ManyToOne(targetEntity: Person::class, inversedBy: 'players', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'person_id', referencedColumnName: 'id')]
    private ?Person $person = null;

    #[ORM\ManyToOne(targetEntity: Turnament::class, inversedBy: 'players')]
    #[ORM\JoinColumn(name: 'turnament_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?Turnament $turnament = null;

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
        $this->pairs = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setNo(int $no): self
    {
        $this->no = $no;

        return $this;
    }

    public function getNo(): int
    {
        return $this->no;
    }

    public function setPerson(?Person $person): self
    {
        $this->person = $person;

        return $this;
    }

    public function getPerson(): ?Person
    {
        return $this->person;
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

    public function setTurnament(?Turnament $turnament): self
    {
        $this->turnament = $turnament;

        return $this;
    }

    public function getTurnament(): ?Turnament
    {
        return $this->turnament;
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

    public function getConfrontations(): array
    {
        $confrontations = [];

        foreach ($this->getPairs() as $i => $pair) {
            $confrontations[$i] = [
                'continuity' => 0,
                'average' => 0,
                'isNS' => false,
                'views' => [],
            ];

            $total = 0;
            $isNS = false;

            foreach ($pair->getBoards() as $board) {
                if ($pair === $board->getPairNS()) {
                    $pairPoints = $board->getPointsNS();
                    $opponentPair = $board->getPairEO();
                    $isNS = true;
                } else {
                    $pairPoints = $board->getPointsEO();
                    $opponentPair = $board->getPairNS();
                }

                $scoreNS = $board->getScoreNS();
                $scoreEO = $board->getScoreEO();

                $confrontations[$i]['views'][] = [
                    'board' => $board,
                    'pairPoints' => $pairPoints,
                    'opponentPair' => $opponentPair,
                    'scoreNS' => $scoreNS,
                    'scoreEO' => $scoreEO,
                    'pairNS' => $board->getPairNS(),
                    'pairEO' => $board->getPairEO(),
                ];

                $confrontations[$i]['continuity'] += 1;
                $total += $pairPoints;
            }

            $confrontations[$i]['average'] = $confrontations[$i]['continuity'] > 0 ? $total / $confrontations[$i]['continuity'] : 0;
            $confrontations[$i]['isNS'] = $isNS;
        }

        return $confrontations;
    }
}
