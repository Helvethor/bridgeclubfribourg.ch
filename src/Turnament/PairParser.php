<?php

declare(strict_types=1);

namespace App\Turnament;

use App\Entity\Pair;
use App\Entity\Turnament;

class PairParser
{
    /**
     * @param array<mixed> $playersByFsb
     * @param array<mixed> $playersByNo
     */
    public function __construct(private readonly Turnament $turnament, private array $playersByFsb, private array $playersByNo)
    {
    }

    public function fetchAll(): array
    {
        return $this->turnament->getIsIndividual() ? $this->fetchIndividual() : $this->fetchPair();
    }

    private function fetchPair(): array
    {
        $pairs = [];

        $handle = fopen($this->turnament->getFiles()['csvPairFile'], 'r');
        if ($handle === false) {
            throw new \RuntimeException("Impossible d'ouvrir le fichier.");
        }

        $header = fgetcsv($handle, 0, ';', escape: '\\');
        if ($header === false) {
            fclose($handle);
            throw new \RuntimeException('Le fichier de paires est vide.');
        }

        $iMp = array_search('MP', $header, true);
        if ($iMp === false) {
            fclose($handle);
            throw new \RuntimeException('Le format du fichier de paires est invalide.');
        }

        $iResult = $iMp + 1;
        $iPv = $iMp + 2;

        while (($line = fgetcsv($handle, 0, ';', escape: '\\')) !== false) {
            $pair = new Pair();
            $pair->setTurnament($this->turnament);
            $pair->addPlayer($this->playersByFsb[strval($line[3] ?? '')]);
            $pair->addPlayer($this->playersByFsb[strval($line[5] ?? '')]);

            $pair->setRank((int) ($line[0] ?? 0));
            $pair->setNo((string) ($line[2] ?? ''));
            $pair->setMp((string) ($line[$iMp] ?? ''));
            $pair->setResult((string) ($line[$iResult] ?? ''));
            $pair->setPv((int) ($line[$iPv] ?? 0));

            $pair->getTurnament()?->addPair($pair);
            foreach ($pair->getPlayers() as $player) {
                $player->addPair($pair);
            }

            $pairs[$pair->getNo()] = $pair;
        }

        fclose($handle);

        return $pairs;
    }

    private function fetchIndividual(): array
    {
        $pairs = [];

        $handle = fopen($this->turnament->getFiles()['csvBoardFile'], 'r');
        if ($handle === false) {
            throw new \RuntimeException("Impossible d'ouvrir le fichier.");
        }

        fgets($handle);

        while (($line = fgetcsv($handle, 0, ';', escape: '\\')) !== false) {
            for ($offset = 2; $offset < 5; $offset += 2) {
                $pairNo = self::getIndividualPairNo($line, $offset);
                if (!array_key_exists($pairNo, $pairs)) {
                    $pairs[$pairNo] = $this->createNewIndividualPair($line, $offset, $pairNo);
                }
            }
        }

        fclose($handle);

        return $pairs;
    }

    private function createNewIndividualPair(array $line, int $offset, string $pairNo): Pair
    {
        $pair = new Pair();
        $pair->setTurnament($this->turnament);
        $pair->getTurnament()?->addPair($pair);
        $pair->setNo($pairNo);
        $pair->addPlayer($this->playersByNo[strval($line[$offset] ?? '')]);
        $pair->addPlayer($this->playersByNo[strval($line[$offset + 1] ?? '')]);

        foreach ($pair->getPlayers() as $player) {
            $player->addPair($pair);
        }

        return $pair;
    }

    public static function getIndividualPairNo(array $line, int $offset): string
    {
        $noN = (int) ($line[$offset] ?? 0);
        $noS = (int) ($line[$offset + 1] ?? 0);

        return $noN < $noS ? $noN . '&' . $noS : $noS . '&' . $noN;
    }
}
