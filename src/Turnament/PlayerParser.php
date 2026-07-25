<?php

declare(strict_types=1);

namespace App\Turnament;

use App\Entity\Person;
use App\Entity\Player;
use App\Entity\Turnament;

class PlayerParser
{
    private readonly string $file;

    public function __construct(private readonly Turnament $turnament, private readonly object $playerRepository, private readonly object $personRepository)
    {
        $this->file = $this->turnament->getFiles()['csvPairFile'];
    }

    public function fetchAll(): array
    {
        if (!($handleR = fopen($this->file, 'r'))) {
            throw new \RuntimeException("Impossible d'ouvrir le fichier.");
        }

        if (!($handleW = fopen($this->file . '.tmp', 'w'))) {
            fclose($handleR);
            throw new \RuntimeException("Impossible d'ouvrir le fichier.");
        }

        $header = fgetcsv($handleR, 0, ';', escape: '\\');
        if ($header === false) {
            fclose($handleR);
            fclose($handleW);
            throw new \RuntimeException('Le fichier est vide.');
        }

        fputcsv($handleW, $header, ';', escape: '\\');
        $iMp = array_search('MP', $header, true);
        if ($iMp === false) {
            fclose($handleR);
            fclose($handleW);
            throw new \RuntimeException('Le fichier de tournoi est invalide.');
        }

        $iResult = $iMp + 1;
        $iPv = $iMp + 2;

        $isIndividual = count($header) === 8;
        $this->turnament->setIsIndividual($isIndividual);

        $playersByFsb = [];
        $playersByNo = [];

        while (($line = fgetcsv($handleR, 0, ';', escape: '\\')) !== false) {
            $person = $this->findPerson($line, 3);
            $line[3] = $person->getFsb();

            $player = new Player();
            $player->setPerson($person);
            $player->setTurnament($this->turnament);
            $player->setNo((int) $line[2]);
            $player->setRank((int) $line[0]);
            $player->setPv((int) $line[$iPv]);
            $player->setMp((string) $line[$iMp]);
            $player->setResult((string) $line[$iResult]);

            $playersByFsb[$person->getFsb()] = $player;
            $playersByNo[$player->getNo()] = $player;

            if (!$isIndividual) {
                $person = $this->findPerson($line, 5);
                $line[5] = $person->getFsb();

                $player = new Player();
                $player->setPerson($person);
                $player->setTurnament($this->turnament);
                $player->setNo((int) $line[2]);
                $player->setRank((int) $line[0]);
                $player->setPv((int) $line[$iPv]);
                $player->setMp((string) $line[$iMp]);
                $player->setResult((string) $line[$iResult]);

                $playersByFsb[$person->getFsb()] = $player;
                $playersByNo[$player->getNo()] = $player;
            }

            fputcsv($handleW, $line, ';', escape: '\\');
        }

        fclose($handleR);
        fclose($handleW);

        rename($this->file . '.tmp', $this->file);

        return [
            'playersByFsb' => $playersByFsb,
            'playersByNo' => $playersByNo,
        ];
    }

    /**
     * @param array<mixed> $line
     */
    private function findPerson(array &$line, int $fsbIndex): Person
    {
        $fsb = (string) ($line[$fsbIndex] ?? '');

        if ($fsb === '') {
            $fsb = $this->personRepository->getNewFakeFsb();
            $line[$fsbIndex] = $fsb;
        }

        $person = $this->personRepository->findOneByFsb($fsb);

        if ($person === null) {
            $names = explode(', ', (string) ($line[$fsbIndex + 1] ?? ''));

            if (count($names) === 2) {
                [$lastName, $firstName] = $names;
            } else {
                if ($names[0] === '') {
                    throw new \Exception("Un nom n'a pas pu être identifié : '" . implode(', ', $line) . "'.");
                }

                $firstName = $names[0];
                $lastName = '';
            }

            $person = $this->personRepository->findOneByNames($firstName, $lastName);

            if ($person === null) {
                $person = new Person();
                $person->setFirstName($firstName);
                $person->setLastName($lastName);
                $person->setFsb($fsb);
            }
        }

        return $person;
    }
}
