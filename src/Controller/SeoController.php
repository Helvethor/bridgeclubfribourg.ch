<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\SitemapService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SeoController extends AbstractController
{
    public function __construct(
        private readonly SitemapService $sitemapService,
    ) {
    }

    #[Route('/robots.txt', name: 'robots_txt', methods: ['GET'])]
    public function robots(Request $request): Response
    {
        $robotsTxt = implode("\n", [
            'User-agent: *',
            'Disallow: /pdf/*',
            'Disallow: /turnament/*',
            'Sitemap: ' . rtrim($request->getSchemeAndHttpHost(), '/') . '/sitemap.xml',
            '',
        ]);

        return new Response(
            $robotsTxt,
            Response::HTTP_OK,
            ['Content-Type' => 'text/plain; charset=UTF-8']
        );
    }

    #[Route('/sitemap.xml', name: 'sitemap_xml', methods: ['GET'])]
    public function sitemap(Request $request): Response
    {
        return new Response(
            $this->sitemapService->generateXml($request),
            Response::HTTP_OK,
            ['Content-Type' => 'application/xml; charset=UTF-8']
        );
    }
}
