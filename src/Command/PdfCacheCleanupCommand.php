<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\PDFGenerationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:pdf-cache:cleanup',
    description: 'Prune expired and oversized cached PDF files'
)]
class PdfCacheCleanupCommand extends Command
{
    public function __construct(
        private readonly PDFGenerationService $pdfGenerationService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $stats = $this->pdfGenerationService->cleanupCache();

        $io->success(sprintf(
            'PDF cache cleanup complete: removed_expired=%d removed_overflow=%d bytes_freed=%d remaining_files=%d remaining_bytes=%d',
            $stats['removed_expired'],
            $stats['removed_overflow'],
            $stats['bytes_freed'],
            $stats['remaining_files'],
            $stats['remaining_bytes']
        ));

        return Command::SUCCESS;
    }
}
