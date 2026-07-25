<?php

declare(strict_types=1);

namespace App\Turnament;

use App\Entity\Board;
use App\Entity\Turnament;

class BoardParser
{
    private readonly bool $isIndividual;
    private readonly string $file;

    public function __construct(private readonly Turnament $turnament, private array $pairs)
    {
        $this->isIndividual = $this->turnament->getIsIndividual();
        $this->file = $this->turnament->getFiles()['csvBoardFile'];
    }

    public function fetchAll(): array
    {
        $boards = [];
        $handle = fopen($this->file, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Impossible d'ouvrir le fichier.");
        }

        fgets($handle);

        while (($line = fgetcsv($handle, 0, ';', escape: '\\')) !== false) {
            $parsed = $this->parseLine($line);

            $board = new Board();
            $board->setTurnament($this->turnament);
            $board->setNo((int) $parsed['no']);
            $board->setContract((string) $parsed['contract']);
            $board->setLeader((string) $parsed['leader']);
            $board->setResult((string) $parsed['result']);
            $board->setLead((string) $parsed['lead']);
            $board->setScore($parsed['score']);

            $board->setPairNS($parsed['pairNS']);
            $parsed['pairNS']?->addBoardsNS($board);
            $board->setPointsNS((string) $parsed['pointsNS']);

            $board->setPairEO($parsed['pairEO']);
            $parsed['pairEO']?->addBoardsEO($board);
            $board->setPointsEO((string) $parsed['pointsEO']);

            $board->getTurnament()?->addBoard($board);
            $boards[] = $board;
        }

        fclose($handle);

        return $boards;
    }

    private function parseLine(array $line): array
    {
        $offset = $this->isIndividual ? 2 : 0;
        $parsedLcr = $this->parseLCR((string) ($line[4 + $offset] ?? ''));

        $result = [
            'no' => $line[0] ?? 0,
            'contract' => $parsedLcr['contract'],
            'leader' => $parsedLcr['leader'],
            'result' => $parsedLcr['result'],
            'lead' => $line[5 + $offset] ?? '',
            'score' => $line[6 + $offset] ?? 0,
        ];

        if ($this->isIndividual) {
            $result['pairNS'] = $this->pairs[PairParser::getIndividualPairNo($line, 2)] ?? null;
            $result['pairEO'] = $this->pairs[PairParser::getIndividualPairNo($line, 4)] ?? null;
            $result['pointsNS'] = $line[9] ?? 0;
            $result['pointsEO'] = $line[11] ?? 0;
        } else {
            $result['pairNS'] = $this->pairs[(string) ($line[2] ?? '')] ?? null;
            $result['pairEO'] = $this->pairs[(string) ($line[3] ?? '')] ?? null;
            $result['pointsNS'] = $line[7] ?? 0;
            $result['pointsEO'] = $line[8] ?? 0;
        }

        return $result;
    }

    private function parseLCR(string $lcr): array
    {
        $leader = '';
        $contract = '';
        $result = '';

        if ($lcr !== '') {
            if ($lcr === 'Pass') {
                $leader = '';
                $contract = 'Pass';
                $result = '';
            } else {
                $leader = preg_replace('/^([a-zA-Z]).+/', '\\1', $lcr) ?? '';
                $contract = preg_replace('/^.+([0-9][a-zA-Z]{1,2}( ?X+)?).*/', '\\1', $lcr) ?? '';
                $result = preg_replace('/^.+(((\+|-)[0-9]+)|=)$/', '\\1', $lcr) ?? '';

                if ($leader === $lcr || $contract === $lcr || $result === $lcr) {
                    $contract = '';
                    $result = '';
                }
            }
        }

        return [
            'leader' => $leader,
            'contract' => $contract,
            'result' => $result,
        ];
    }
}
