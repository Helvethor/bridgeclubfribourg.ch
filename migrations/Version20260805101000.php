<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260805101000 extends AbstractMigration
{
    private const OLD_CSV_BASE_DIR = '/app/public/upload/turnament/csv';
    private const NEW_CSV_BASE_DIR = '/app/public/turnament/csv';

    public function getDescription(): string
    {
        return 'Move tournament CSV files from /public/upload/turnament/csv to /public/turnament/csv and rewrite turnament.files JSON references.';
    }

    public function isTransactional(): bool
    {
        // File system operations are not transactional.
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->ensureDirectory(self::NEW_CSV_BASE_DIR . '/pair');
        $this->ensureDirectory(self::NEW_CSV_BASE_DIR . '/board');

        $this->moveCsvTree(self::OLD_CSV_BASE_DIR, self::NEW_CSV_BASE_DIR);
        $this->rewriteTurnamentFileReferences(self::OLD_CSV_BASE_DIR, self::NEW_CSV_BASE_DIR);
    }

    public function down(Schema $schema): void
    {
        $this->ensureDirectory(self::OLD_CSV_BASE_DIR . '/pair');
        $this->ensureDirectory(self::OLD_CSV_BASE_DIR . '/board');

        $this->moveCsvTree(self::NEW_CSV_BASE_DIR, self::OLD_CSV_BASE_DIR);
        $this->rewriteTurnamentFileReferences(self::NEW_CSV_BASE_DIR, self::OLD_CSV_BASE_DIR);
    }

    private function rewriteTurnamentFileReferences(string $fromPrefix, string $toPrefix): void
    {
        $rows = $this->connection->fetchAllAssociative('SELECT id, files FROM turnament');

        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $rawFiles = $row['files'];

            $decoded = is_string($rawFiles) ? json_decode($rawFiles, true) : $rawFiles;
            if (!is_array($decoded)) {
                continue;
            }

            $updated = $this->replacePathPrefixInArray($decoded, $fromPrefix, $toPrefix);

            if ($updated !== $decoded) {
                $this->connection->update(
                    'turnament',
                    ['files' => json_encode($updated, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)],
                    ['id' => $id]
                );

                $this->write(sprintf('Updated file references for turnament id=%d', $id));
            }
        }
    }

    /**
     * @param array<mixed> $value
     * @return array<mixed>
     */
    private function replacePathPrefixInArray(array $value, string $fromPrefix, string $toPrefix): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->replacePathPrefixInArray($item, $fromPrefix, $toPrefix);
                continue;
            }

            if (is_string($item) && str_starts_with($item, $fromPrefix)) {
                $value[$key] = $toPrefix . substr($item, strlen($fromPrefix));
            }
        }

        return $value;
    }

    private function moveCsvTree(string $fromDir, string $toDir): void
    {
        if (!is_dir($fromDir)) {
            $this->write(sprintf('Source directory does not exist, skipping move: %s', $fromDir));
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($fromDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $entry) {
            $sourcePath = $entry->getPathname();
            $relativePath = substr($sourcePath, strlen($fromDir) + 1);
            $targetPath = $toDir . '/' . $relativePath;

            if ($entry->isDir()) {
                $this->ensureDirectory($targetPath);
                continue;
            }

            $this->ensureDirectory((string) dirname($targetPath));

            if (file_exists($targetPath)) {
                // Target already exists; remove source duplicate to complete the migration.
                if (!@unlink($sourcePath)) {
                    throw new \RuntimeException(sprintf('Unable to remove duplicate source file: %s', $sourcePath));
                }
                continue;
            }

            if (!@rename($sourcePath, $targetPath)) {
                if (!@copy($sourcePath, $targetPath)) {
                    throw new \RuntimeException(sprintf('Unable to move file from %s to %s', $sourcePath, $targetPath));
                }

                if (!@unlink($sourcePath)) {
                    throw new \RuntimeException(sprintf('Unable to remove source file after copy: %s', $sourcePath));
                }
            }
        }
    }

    private function ensureDirectory(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        if (!@mkdir($path, 0775, true) && !is_dir($path)) {
            throw new \RuntimeException(sprintf('Unable to create directory: %s', $path));
        }
    }
}
