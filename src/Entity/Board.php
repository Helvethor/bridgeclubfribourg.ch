<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Board
 */
#[ORM\Entity(repositoryClass: \App\Repository\BoardRepository::class)]
#[ORM\Table(name: 'board')]
class Board
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Turnament::class, inversedBy: 'boards')]
    #[ORM\JoinColumn(name: 'turnamentId', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?Turnament $turnament = null;

    #[ORM\ManyToOne(targetEntity: Pair::class, inversedBy: 'boardsNS')]
    #[ORM\JoinColumn(name: 'playerNSId', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?Pair $pairNS = null;

    #[ORM\ManyToOne(targetEntity: Pair::class, inversedBy: 'boardsEO')]
    #[ORM\JoinColumn(name: 'playerEOId', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?Pair $pairEO = null;

    /**
     * @var string
     */
    #[ORM\Column(name: 'contract', type: 'string', length: 32)]
    private $contract;

    /**
     * @var string
     */
    #[ORM\Column(name: 'leader', type: 'string', length: 32)]
    private $leader;

    /**
     * @var string
     */
    #[ORM\Column(name: 'lead', type: 'string', length: 32)]
    private $lead;

    /**
     * @var string
     */
    #[ORM\Column(name: 'result', type: 'string', length: 32)]
    private $result;

    /**
     * @var string
     */
    #[ORM\Column(name: 'scoreNS', type: 'string', length: 32, nullable: true)]
    private ?string $scoreNS = null;

    /**
     * @var string
     */
    #[ORM\Column(name: 'scoreEO', type: 'string', length: 32, nullable: true)]
    private ?string $scoreEO = null;

    /**
     * @var float
     */
    #[ORM\Column(name: 'pointsNS', type: 'decimal', precision: 4, scale: 2, nullable: true)]
    private $pointsNS;

    /**
     * @var float
     */
    #[ORM\Column(name: 'pointsEO', type: 'decimal', precision: 4, scale: 2, nullable: true)]
    private $pointsEO;

    /**
     * @var int
     */
    #[ORM\Column(name: 'no', type: 'smallint')]
    private $no;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'hasBeenCorrected', type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $hasBeenCorrected = false;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set contract
     *
     * @param string $contract
     *
     * @return Board
     */
    public function setContract($contract)
    {
        $this->contract = $contract;

        return $this;
    }

    /**
     * Get contract
     *
     * @return string
     */
    public function getContract()
    {
        if ($this->getHasBeenCorrected()) {
            return '/';
        }
        return $this->contract;
    }

    /**
     * Set leader
     *
     * @param string $leader
     *
     * @return Board
     */
    public function setLeader($leader)
    {
        $this->leader = $leader;

        return $this;
    }

    /**
     * Get leader
     *
     * @return string
     */
    public function getLeader()
    {
        return $this->leader;
    }

    /**
     * Set lead
     *
     * @param string $lead
     *
     * @return Board
     */
    public function setLead($lead)
    {
        $this->lead = $lead;

        return $this;
    }

    /**
     * Get lead
     *
     * @return string
     */
    public function getLead()
    {
        if ($this->getHasBeenCorrected()) {
            return '/';
        }
        return $this->lead;
    }

    /**
     * Set result
     *
     * @param string $result
     *
     * @return Board
     */
    public function setResult($result)
    {
        $this->result = $result;

        return $this;
    }

    /**
     * Get result
     *
     * @return string
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Set score (accepts "NS / EO" format or numeric format)
     */
    public function setScore(?string $score): self
    {
        $split = $this->splitScoreNSandEO($score);
        $this->scoreNS = $split['NS'];
        $this->scoreEO = $split['EO'];

        if (isset($split['hasBeenCorrected']) && $split['hasBeenCorrected']) {
            $this->setHasBeenCorrected(true);
        }

        return $this;
    }
 
	/**
	 * Split score string into NS and EO parts
	 *
	 * @return array{NS: string, EO: string, hasBeenCorrected?: bool}
	 */
	private function splitScoreNSandEO(?string $score): array
	{
		if ($score === null) {
			return ['NS' => '', 'EO' => ''];
		}

		// If score contains "/", split it
		if (strpos($score, '/') !== false) {
			$parts = explode('/', $score);
			return [
				'NS' => trim($parts[0] ?? ''),
				'EO' => trim($parts[1] ?? ''),
                'hasBeenCorrected' => true,
			];
		}

		// If score is a numeric
		if (is_numeric($score)) {
			$scoreInt = (float) $score;
			if ($scoreInt >= 0) {
				return ['NS' => (string) $scoreInt, 'EO' => ''];
			} else {
				return ['NS' => '', 'EO' => (string) (-$scoreInt)];
			}
		}

		// Default: return empty strings
		return ['NS' => '', 'EO' => ''];
	}

	/**
	 * Get score NS (North-South)
	 */
	public function getScoreNS(): string
	{
		return $this->scoreNS ?? '';
	}

	/**
	 * Get score EO (East-West)
	 */
	public function getScoreEO(): string
	{
		return $this->scoreEO ?? '';
	}

    /**
     * Set pointsNS
     *
     * @param string $pointsNS
     *
     * @return Board
     */
    public function setPointsNS($pointsNS)
    {
        $this->pointsNS = $pointsNS;

        return $this;
    }

    /**
     * Get pointsNS
     *
     * @return float|int
     */
    public function getPointsNS(): float|int
    {
        if ($this->getHasBeenCorrected()) {
            if (trim($this->scoreNS ?? '') === '---' && trim($this->scoreEO ?? '') === '---') {
                return $this->parseNumericValue($this->pairNS?->getResult() ?? '0');
            }

            return $this->resolveCorrectedPoints($this->scoreNS, $this->pairEO, $this->pointsNS, $this->pointsEO);
        }

        return $this->getRawPointsPercent($this->pointsNS, $this->pointsEO);
    }

    /**
     * Set pointsEO
     *
     * @param string $pointsEO
     *
     * @return Board
     */
    public function setPointsEO($pointsEO)
    {
        $this->pointsEO = $pointsEO;

        return $this;
    }

    /**
     * Get pointsEO
     *
     * @return float|int
     */
    public function getPointsEO(): float|int
    {
        if ($this->getHasBeenCorrected()) {
            if (trim($this->scoreNS ?? '') === '---' && trim($this->scoreEO ?? '') === '---') {
                return $this->parseNumericValue($this->pairEO?->getResult() ?? '0');
            }

            return $this->resolveCorrectedPoints($this->scoreEO, $this->pairNS, $this->pointsEO, $this->pointsNS);
        }

        return $this->getRawPointsPercent($this->pointsEO, $this->pointsNS);
    }

    private function resolveCorrectedPoints(?string $score, ?Pair $pair, mixed $sidePoints, mixed $otherSidePoints): float
    {
        $rawScore = trim((string) $score);
        $pairResult = $this->parseNumericValue($pair?->getResult() ?? '0');

        if (preg_match('/^([+-]?\d+(?:[.,]\d+)?)\s*([+-])\s*%$/', $rawScore, $matches) === 1) {
            $value = $this->parseNumericValue($matches[1]);

            return $matches[2] === '+'
                ? max($value, $pairResult)
                : min($value, $pairResult);
        }

        if (preg_match('/^([+-]?\d+(?:[.,]\d+)?)\s*%$/', $rawScore, $matches) === 1) {
            return $this->parseNumericValue($matches[1]);
        }

        return $this->getRawPointsPercent($sidePoints, $otherSidePoints);
    }

    private function getRawPointsPercent(mixed $sidePoints, mixed $otherSidePoints): float
    {
        $side = (float) $sidePoints;
        $other = (float) $otherSidePoints;
        $total = $side + $other;

        if ($total === 0.0) {
            return 0.0;
        }

        return $side / $total * 100;
    }

    private function parseNumericValue(string $raw): float
    {
        $normalized = str_replace(',', '.', trim($raw));

        return (float) preg_replace('/[^0-9+\-.]/', '', $normalized);
    }

    /**
     * Set no
     *
     * @param integer $no
     *
     * @return Board
     */
    public function setNo($no)
    {
        $this->no = $no;

        return $this;
    }

    /**
     * Get no
     *
     * @return integer
     */
    public function getNo()
    {
        return $this->no;
    }

    /**
     * Set hasBeenCorrected
     *
     * @param bool $hasBeenCorrected
     *
     * @return Board
     */
    public function setHasBeenCorrected(bool $hasBeenCorrected): self
    {
        $this->hasBeenCorrected = $hasBeenCorrected;

        return $this;
    }

    /**
     * Get hasBeenCorrected
     *
     * @return bool
     */
    public function getHasBeenCorrected(): bool
    {
        return $this->hasBeenCorrected;
    }

    /**
     * Set turnament
     *
     * @param Turnament $turnament
     *
     * @return Board
     */
    public function setTurnament(?Turnament $turnament = null)
    {
        $this->turnament = $turnament;

        return $this;
    }

    /**
     * Get turnament
     *
     * @return Turnament
     */
    public function getTurnament()
    {
        return $this->turnament;
    }

    /**
     * Set pairNS
     *
     * @param Pair $pairNS
     *
     * @return Board
     */
    public function setPairNS(?Pair $pairNS = null)
    {
        $this->pairNS = $pairNS;

        return $this;
    }

    /**
     * Get pairNS
     *
     * @return Pair
     */
    public function getPairNS()
    {
        return $this->pairNS;
    }

    /**
     * Set pairEO
     *
     * @param Pair $pairEO
     *
     * @return Board
     */
    public function setPairEO(?Pair $pairEO = null)
    {
        $this->pairEO = $pairEO;

        return $this;
    }

    /**
     * Get pairEO
     *
     * @return Pair
     */
    public function getPairEO()
    {
        return $this->pairEO;
    }
}
