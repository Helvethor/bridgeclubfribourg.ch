<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\PDFGenerationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

class PDFController extends AbstractController
{
    public function __construct(private readonly PDFGenerationService $pdfGenerationService)
    {
    }

    #[Route('/pdf/{url}', name: 'pdf', requirements: ['url' => '.+'])]
    public function pdf(string $url): BinaryFileResponse
    {
        $decodedUrl = urldecode($url);
        $pdfTargetUrl = $decodedUrl;
        if (str_contains($pdfTargetUrl, '?')) {
            $pdfTargetUrl .= '&pdf=1';
        } else {
            $pdfTargetUrl .= '?pdf=1';
        }

        $result = $this->pdfGenerationService->generate($pdfTargetUrl);

        $response = new BinaryFileResponse($result['file']);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $result['filename']);
        $response->deleteFileAfterSend(true);

        return $response;
    }
}
