<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\PDFGenerationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

class PDFController extends AbstractController
{
    private const ALLOWED_HOSTS = [
        'bridgeclubfribourg.ch',
        'www.bridgeclubfribourg.ch',
    ];

    public function __construct(
        private readonly PDFGenerationService $pdfGenerationService,
        private readonly RateLimiterFactory $pdfLimiter,
    ) {
    }

    #[Route('/pdf/{url}', name: 'pdf', requirements: ['url' => '.+'])]
    public function pdf(string $url, Request $request): Response
    {
        $limiter = $this->pdfLimiter->create($request->getClientIp());
        if (!$limiter->consume(1)->isAccepted()) {
            return new Response(
                'Too many PDF requests. Please try again later.',
                Response::HTTP_TOO_MANY_REQUESTS,
                ['Retry-After' => '60']
            );
        }

        $decodedUrl = urldecode($url);

        $host = parse_url($decodedUrl, PHP_URL_HOST);
        if ($host === null || !in_array($host, self::ALLOWED_HOSTS, true)) {
            throw $this->createNotFoundException();
        }

        $pdfTargetUrl = $decodedUrl;
        if (str_contains($pdfTargetUrl, '?')) {
            $pdfTargetUrl .= '&pdf=1';
        } else {
            $pdfTargetUrl .= '?pdf=1';
        }

        $result = $this->pdfGenerationService->generate($pdfTargetUrl);

        $response = new BinaryFileResponse($result['file']);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $result['filename']);

        return $response;
    }
}
