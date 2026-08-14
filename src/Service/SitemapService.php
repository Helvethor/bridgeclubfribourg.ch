<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SitemapService
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function generateXml(Request $request): string
    {
        $urls = [
            [
                'loc' => $this->absoluteUrl($request, 'default'),
                'changefreq' => 'daily',
                'priority' => '1.0',
            ],
            [
                'loc' => $this->absoluteUrl($request, 'lessons'),
                'changefreq' => 'weekly',
                'priority' => '0.6',
            ],
        ];

        $xml = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        foreach ($urls as $url) {
            $xml[] = '  <url>';
            $xml[] = '    <loc>' . $this->xmlEscape($url['loc']) . '</loc>';
            $xml[] = '    <changefreq>' . $url['changefreq'] . '</changefreq>';
            $xml[] = '    <priority>' . $url['priority'] . '</priority>';
            $xml[] = '  </url>';
        }

        $xml[] = '</urlset>';

        return implode("\n", $xml) . "\n";
    }

    private function absoluteUrl(Request $request, string $routeName, array $params = []): string
    {
        $path = $this->urlGenerator->generate($routeName, $params, UrlGeneratorInterface::ABSOLUTE_PATH);

        return rtrim($request->getSchemeAndHttpHost(), '/') . $path;
    }

    private function xmlEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
