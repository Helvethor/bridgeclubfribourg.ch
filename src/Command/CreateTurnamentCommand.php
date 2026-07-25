<?php

declare(strict_types=1);

namespace App\Command;

use App\Turnament\Creator;
use App\Turnament\LocalUploadedFile;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-turnament',
    description: 'Import one or more XLS tournament files'
)]
class CreateTurnamentCommand extends Command
{
    public function __construct(
        private readonly Creator $creator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('files', InputArgument::IS_ARRAY, 'XLS files to import');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        /** @var string[] $files */
        $files = $input->getArgument('files');

        foreach ($files as $file) {
            $io->text("Importing: $file");
            $uploadedFile = new LocalUploadedFile($file);
            try {
                $result = $this->creator->createFromFile($uploadedFile);

                if ($result['status'] === 'success') {
                    $io->success($result['message']);
                } else if ($result['status'] === 'warning') {
                    $io->warning($result['message']);
                } else {
                    $io->error($result['message']);
                    return Command::FAILURE;
                }
            } finally {
                unset($uploadedFile, $result);
                gc_collect_cycles();
            }
        }

        return Command::SUCCESS;
    }
}
