<?php

declare(strict_types=1);

namespace App\Turnament;

use App\Entity\Turnament;
use App\Entity\Player;
use App\Entity\Person;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv as CsvWriter;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Creator
{
    protected string $turnamentDir;
    protected string $xlsDir;
    protected string $csvPairDir;
    protected string $csvBoardDir;
    protected ?Turnament $turnament = null;
    /** @var array<mixed> */
    protected array $boards = [];
    /** @var array<mixed> */
    protected array $pairs = [];
    /** @var array<mixed> */
    protected array $players = [];

    public function __construct(string $turnamentDir, protected EntityManagerInterface $em)
    {
        $this->turnamentDir = realpath($turnamentDir) ?: $turnamentDir;
        $this->xlsDir = $this->turnamentDir . '/xls';
        $this->csvPairDir = $this->turnamentDir . '/csv/pair';
        $this->csvBoardDir = $this->turnamentDir . '/csv/board';
    }

    public function createFromLink(string $externalLink, \DateTimeInterface $date): array
    {
        try {
            $this->turnament = new Turnament();
            $this->boards = [];
            $this->pairs = [];
            $this->players = [];

            if ($this->em->getRepository(Turnament::class)->findOneBy(['date' => $date]) !== null) {
                return [
                    'status' => 'warning',
                    'message' => 'Un tournoi existe déjà pour la date demandée : ' . $date->format('d.m.Y'),
                ];
            }

            $this->turnament->setType('external');
            $this->turnament->setFiles([]);
            $this->turnament->setDate($date);
            $this->turnament->setIsExternal(true);
            $this->turnament->setExternalLink($externalLink);
            $this->turnament->setDow(strtolower((string) $date->format('l')));
        } catch (\Exception $e) {
            return [
                'status' => 'warning',
                'message' => 'Un problème est survenu durant la sauvegarde du tournois : ' . $e->getMessage(),
            ];
        }

        try {
            $this->persist();

            return [
                'status' => 'success',
                'message' => 'Le tournois a été ajouté avec succès.',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'danger',
                'message' => 'Un problème est survenu durant la sauvegarde du tournois : ' . $e->getMessage(),
            ];
        } finally {
            $this->cleanup();
        }
    }

    public function createFromFile(UploadedFile $file): array
    {
        try {
            $turnament = new Turnament();
            $fileName = $file->getClientOriginalName();
            $filePath = $file->getRealPath();
            $dateId = $this->parseDateId($fileName);
            $date = \DateTime::createFromFormat('d.m.Y', $dateId);
            if ($date === false) {
                throw new \RuntimeException('Date invalide.');
            }

            $dow = strtolower((string) $date->format('l'));

            if ($this->em->getRepository(Turnament::class)->findOneBy(['date' => $date]) !== null) {
                return [
                    'status' => 'warning',
                    'message' => 'Un tournoi existe déjà pour la date demandée : ' . $dateId,
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Un problème est survenu durant la lecture du fichier : ' . $e->getMessage(),
            ];
        }

        try {
            $tempPath = $filePath;
            if ($tempPath === false) {
                throw new \RuntimeException('Impossible de récupérer le fichier.');
            }

            copy($tempPath, $this->xlsDir . '/' . $dateId . '.xls');
            $files = $this->xls2csv($dateId);
        } catch (\Exception $e) {
            return [
                'status' => 'danger',
                'message' => 'Un problème est survenu durant la conversion du fichier : ' . $e->getMessage(),
            ];
        }

        try {
            $turnament->setType($this->parseType($files['csvPairFile']));
            $turnament->setFiles($files);
            $turnament->setDate($date);
            $turnament->setDow($dow);
            $turnament->setIsExternal(false);
            $this->turnament = $turnament;
        } catch (\Exception $e) {
            return [
                'status' => 'warning',
                'message' => 'Un problème est survenu durant la sauvegarde du tournois : ' . $e->getMessage(),
            ];
        }

        try {
            $parser = new PlayerParser(
                $turnament,
                $this->em->getRepository(Player::class),
                $this->em->getRepository(Person::class)
            );
            $parsedPlayers = $parser->fetchAll();
            $this->players = $parsedPlayers['playersByFsb'] ?? [];
            $playersByNo = $parsedPlayers['playersByNo'] ?? [];
        } catch (\Exception $e) {
            return [
                'status' => 'danger',
                'message' => 'Un problème est survenu durant la lecture des joueurs : ' . $e->getMessage(),
            ];
        }

        try {
            $this->pairs = new PairParser($turnament, $this->players, $playersByNo ?? [])->fetchAll();
        } catch (\Exception $e) {
            return [
                'status' => 'danger',
                'message' => 'Un problème est survenu durant la lecture des paires : ' . $e->getMessage(),
            ];
        }

        try {
            $this->boards = new BoardParser($turnament, $this->pairs)->fetchAll();
        } catch (\Exception $e) {
            return [
                'status' => 'danger',
                'message' => 'Un problème est survenu durant la lecture des donnes : ' . $e->getMessage(),
            ];
        }

        try {
            $this->persist();

            return [
                'status' => 'success',
                'message' => 'Le tournois a été ajouté avec succès.',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'danger',
                'message' => 'Un problème est survenu durant la sauvegarde du tournois : ' . $e->getMessage(),
            ];
        } finally {
            $this->cleanup();
        }
    }

    private function parseDateId(string $string): string
    {
        $dateId = preg_replace('/.*(\d{2})[\.\/_-](\d{2})[\.\/_-](\d{4}).*/', '\\1.\\2.\\3', $string);

        if ($dateId === $string) {
            throw new \Exception('Le nom du fichier doit contenir la date du tournois (jj.mm.yyyy).');
        }

        return $dateId;
    }

    private function parseType(string $pairFile): string
    {
        $handle = fopen($pairFile, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Impossible d’ouvrir le fichier de paires.');
        }

        $header = fgetcsv($handle, 0, ';', escape: '\\');
        fclose($handle);

        if ($header === false) {
            throw new \RuntimeException('Le fichier de paires est vide.');
        }

        return match ($header[count($header) - 2] ?? null) {
            '%' => '%',
            'IMP/D' => 'IMP',
            default => throw new \Exception('Le type du tournois est inconnu (IMP/%).'),
        };
    }

    private function xls2csv(string $dateId): array
    {
        $xlsFile = $this->xlsDir . '/' . $dateId . '.xls';
        $csvPairFile = $this->csvPairDir . '/' . $dateId . '.csv';
        $csvBoardFile = $this->csvBoardDir . '/' . $dateId . '.csv';

        if (!class_exists(IOFactory::class) || !class_exists(CsvWriter::class)) {
            throw new \RuntimeException('La bibliothèque PhpSpreadsheet n\'est pas disponible. Installez phpoffice/phpspreadsheet pour importer des fichiers Excel.');
        }

        $reader = IOFactory::createReaderForFile($xlsFile);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($xlsFile);

        // Write pair sheet (first sheet)
        $pairSpreadsheet = new Spreadsheet();
        $pairSpreadsheet->removeSheetByIndex(0);
        $pairSpreadsheet->addSheet($spreadsheet->getSheet(0));

        $pairWriter = new CsvWriter($pairSpreadsheet);
        $pairWriter->setDelimiter(';');
        $pairWriter->setEnclosure('');
        $pairWriter->setLineEnding("\r\n");
        $pairWriter->save($csvPairFile);

        // Write board sheets (remaining sheets)
        $boardsheetCount = $spreadsheet->getSheetCount() - 1;
        $wh = fopen($csvBoardFile, 'w');
        if ($wh === false) {
            throw new \RuntimeException('Impossible de créer le fichier de tableaux.');
        }

        for ($i = 0; $i < $boardsheetCount; $i++) {
            $file = $csvBoardFile . '.tmp';
            
            // Create temporary spreadsheet with just this board sheet
            $boardSpreadsheet = new Spreadsheet();
            $boardSpreadsheet->removeSheetByIndex(0);
            $boardSpreadsheet->addSheet($spreadsheet->getSheet($i + 1));

            $boardWriter = new CsvWriter($boardSpreadsheet);
            $boardWriter->setDelimiter(';');
            $boardWriter->setEnclosure('');
            $boardWriter->setLineEnding("\r\n");
            $boardWriter->save($file);

            $rh = fopen($file, 'r');
            if ($rh === false) {
                throw new \RuntimeException('Impossible de lire le fichier de tableaux temporaire.');
            }

            if ($i > 0) {
                fgets($rh);
            }

            while (!feof($rh)) {
                $line = fgets($rh);
                if ($line !== false) {
                    fwrite($wh, $line);
                }
            }

            fclose($rh);
            unlink($file);
        }

        fclose($wh);

        return [
            'xlsFile' => $xlsFile,
            'csvPairFile' => $csvPairFile,
            'csvBoardFile' => $csvBoardFile,
        ];
    }

    private function persist(): void
    {
        if ($this->turnament === null) {
            throw new \RuntimeException('Turnament is not initialized.');
        }

        $this->em->persist($this->turnament);

        foreach ($this->players as $player) {
            $this->em->persist($player);
        }

        foreach ($this->pairs as $pair) {
            $this->em->persist($pair);
        }

        foreach ($this->boards as $board) {
            $this->em->persist($board);
        }

        $this->em->flush();
        $this->em->clear();
    }

    private function cleanup(): void
    {
        $this->turnament = null;
        $this->boards = [];
        $this->pairs = [];
        $this->players = [];

        gc_collect_cycles();
    }
}
