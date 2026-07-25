<?php

declare(strict_types=1);

namespace App\Turnament;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Wraps a local file path as an UploadedFile so it can be passed to
 * Creator::createFromFile() without going through an HTTP upload.
 */
class LocalUploadedFile extends UploadedFile
{
    public function __construct(string $path)
    {
        if (!file_exists($path)) {
            throw new \RuntimeException(sprintf('File not found: %s', $path));
        }

        if (!is_file($path)) {
            throw new \RuntimeException(sprintf('Path is not a file: %s', $path));
        }

        // Get mime type using finfo or default to octet-stream
        $mimeType = @mime_content_type($path) ?: 'application/octet-stream';

        // Initialize as uploaded file with UPLOAD_ERR_OK and test=true
        parent::__construct($path, basename($path), $mimeType, UPLOAD_ERR_OK, true);
    }
}
