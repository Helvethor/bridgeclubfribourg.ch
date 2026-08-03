<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Turnament;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:add-custom-file',
    description: 'Attach a custom file to an existing tournament by date'
)]
class AddCustomFileCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly string $turnamentUploadDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('files', InputArgument::IS_ARRAY, 'Paths to the custom files to attach');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        /** @var string[] $files */
        $files = $input->getArgument('files');

        $customDir = $this->turnamentUploadDir . '/custom';

        $turnamentRepo = $this->em->getRepository(Turnament::class);

        foreach ($files as $file) {
            $path = $customDir . '/' . basename($file);
            copy($file, $path);

            $rawDate = preg_replace('/.*(\d{2}\.\d{2}\.\d{4}).*$/', '\1', basename($file));
            $date = \DateTime::createFromFormat('d.m.Y', $rawDate ?: '');

            if ($date === false) {
                $io->error("$file: impossible de lire la date dans le nom du fichier");
                continue;
            }

            $turnament = $turnamentRepo->findOneBy(['date' => $date]);

            if ($turnament !== null) {
                $turnament->addCustomFile($path);
                $this->em->persist($turnament);
                $this->em->flush();
                $io->success("$file: fichier personnalisé ajouté");
            } else {
                $io->error("$file: tournois introuvable pour la date $rawDate");
            }
        }

        return Command::SUCCESS;
    }
}
