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
        $pdfDir   = $this->kernel->getProjectDir() . '/pdf';
        $file     = $pdfDir . '/' . $filename;

        if (!is_dir($pdfDir) && !mkdir($pdfDir, 0775, true) && !is_dir($pdfDir)) {
            throw new \RuntimeException(sprintf('Unable to create PDF directory: %s', $pdfDir));
        }

        try {
            $browsershot = Browsershot::url($url)
                ->setNodeBinary('/usr/bin/node')
                ->setNpmBinary('/usr/bin/npm')
                ->setChromePath('/usr/bin/chromium')
                ->disableJavascript()
                ->setOption('emulateMedia', 'print')
                ->noSandbox();

            if ($this->kernel->getEnvironment() !== 'prod') {
                $browsershot
                    ->ignoreHttpsErrors()
                    ->addChromiumArguments(['ignore-certificate-errors' => true]);
            }

            $browsershot
                ->format('A4')
                ->showBackground()
                ->save($file);
        } catch (\Throwable $exception) {
            throw new \RuntimeException('PDF generation failed: ' . $exception->getMessage(), 0, $exception);
        }

        if (!is_file($file)) {
            throw new \RuntimeException('PDF generation failed.');
        }

        $response = new BinaryFileResponse($file);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
        $response->deleteFileAfterSend(true);

        return $response;
    }
}
