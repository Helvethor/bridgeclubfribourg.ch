<?php

declare(strict_types=1);

namespace App\Controller;

use Spatie\Browsershot\Browsershot;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;

class PDFController extends AbstractController
{
    public function __construct(private readonly KernelInterface $kernel)
    {
    }

    #[Route('/pdf/{url}', name: 'pdf', requirements: ['url' => '.+'])]
    public function pdf(string $url): BinaryFileResponse
    {
        $url      = urldecode($url);
        $path     = parse_url($url, PHP_URL_PATH) ?? '';
        $filename = ltrim(str_replace('/', '-', $path), '-') . '-' . date('YmdHis') . '.pdf';
        $pdfDir   = $this->kernel->getProjectDir() . '/var/pdf';
        $file     = $pdfDir . '/' . $filename;

        if (!is_dir($pdfDir) && !mkdir($pdfDir, 0775, true) && !is_dir($pdfDir)) {
            throw new \RuntimeException(sprintf('Unable to create PDF directory: %s', $pdfDir));
        }

        $errors = [];
        foreach ($this->buildPdfTargetCandidates($url) as $candidate) {
            try {
                @unlink($file);

                $browsershot = Browsershot::url($candidate)
                    ->setNodeBinary('/usr/bin/node')
                    ->setNodeModulePath('/usr/local/lib/node_modules')
                    ->setChromePath('/usr/lib/chromium/chromium')
                    ->disableJavascript()
                    ->setOption('emulateMedia', 'print')
                    ->setOption('args', ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage'])
                    ->noSandbox();

                if (str_starts_with($candidate, 'https://') && $this->kernel->getEnvironment() !== 'prod') {
                    $browsershot
                        ->ignoreHttpsErrors()
                        ->addChromiumArguments(['ignore-certificate-errors' => true]);
                }

                $browsershot
                    ->format('A4')
                    ->showBackground()
                    ->save($file);

                if (is_file($file)) {
                    break;
                }
            } catch (\Throwable $exception) {
                $errors[] = sprintf('target=%s message=%s', $candidate, $exception->getMessage());
                error_log('[PDF] generation failed: target=' . $candidate . ' message=' . $exception->getMessage());
            }
        }

        if (!is_file($file)) {
            throw new \RuntimeException('PDF generation failed. ' . implode(' | ', $errors));
        }

        $response = new BinaryFileResponse($file);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
        $response->deleteFileAfterSend(true);

        return $response;
    }

    /**
     * @return list<string>
     */
    private function buildPdfTargetCandidates(string $url): array
    {
        $candidates = [$url];
        $host       = parse_url($url, PHP_URL_HOST);
        $path       = parse_url($url, PHP_URL_PATH) ?: '/';
        $query      = parse_url($url, PHP_URL_QUERY);
        $fragment   = parse_url($url, PHP_URL_FRAGMENT);

        if ($host !== null && in_array($host, ['bridgeclubfribourg.ch', 'www.bridgeclubfribourg.ch'], true)) {
            $internal = 'http://127.0.0.1' . $path;
            if ($query !== null && $query !== '') {
                $internal .= '?' . $query;
            }
            if ($fragment !== null && $fragment !== '') {
                $internal .= '#' . $fragment;
            }

            $candidates[] = $internal;
        }

        return array_values(array_unique($candidates));
    }
}
