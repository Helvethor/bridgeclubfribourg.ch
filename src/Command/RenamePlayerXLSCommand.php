<?php

declare(strict_types=1);

namespace App\Command;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:rename-player-xls',
    description: 'Rename a player in XLS tournament files'
)]
class RenamePlayerXLSCommand extends Command
{
    /** @var list<int> */
    private const NAME_COLUMNS = [4, 6];

    protected function configure(): void
    {
        $this
            ->addArgument('firstname', InputArgument::REQUIRED, 'Current first name')
            ->addArgument('lastname', InputArgument::REQUIRED, 'Current last name')
            ->addArgument('files', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'XLS files to edit in place')
            ->addOption('new_firstname', null, InputOption::VALUE_REQUIRED, 'Replacement first name')
            ->addOption('new_lastname', null, InputOption::VALUE_REQUIRED, 'Replacement last name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $firstname = (string) $input->getArgument('firstname');
        $lastname = (string) $input->getArgument('lastname');
        $newFirstname = (string) ($input->getOption('new_firstname') ?? $firstname);
        $newLastname = (string) ($input->getOption('new_lastname') ?? $lastname);
        $oldName = $lastname . ', ' . $firstname;
        $newName = $newLastname . ', ' . $newFirstname;
        $hasFailure = false;

        /** @var list<string> $files */
        $files = $input->getArgument('files');
        foreach ($files as $file) {
            try {
                if (!is_file($file) || !is_readable($file) || !is_writable($file)) {
                    throw new \RuntimeException('File must exist and be readable and writable.');
                }

                $spreadsheet = IOFactory::load($file);
                $matches = 0;

                foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                    foreach ($worksheet->getRowIterator() as $row) {
                        foreach (self::NAME_COLUMNS as $columnIndex) {
                            $coordinate = Coordinate::stringFromColumnIndex($columnIndex + 1) . $row->getRowIndex();
                            $cell = $worksheet->getCell($coordinate);
                            if ($cell->getValue() === $oldName) {
                                $cell->setValue($newName);
                                $matches++;
                            }
                        }
                    }
                }

                if ($matches > 0) {
                    $writer = IOFactory::createWriter($spreadsheet, IOFactory::identify($file));
                    $writer->save($file);
                    $io->success(sprintf('%s: renamed %d occurrence(s).', $file, $matches));
                } else {
                    $io->warning(sprintf('%s: player not found.', $file));
                }
            } catch (\Throwable $exception) {
                $hasFailure = true;
                $io->error(sprintf('%s: %s', $file, $exception->getMessage()));
            }
        }

        return $hasFailure ? Command::FAILURE : Command::SUCCESS;
    }
}