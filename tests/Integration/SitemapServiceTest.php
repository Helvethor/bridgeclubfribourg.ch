<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Service\SitemapService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;

class SitemapServiceTest extends KernelTestCase
{
    public function testGenerateXml(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $sitemapService = $container->get(SitemapService::class);
        $request = Request::create('https://bridgeclubfribourg.ch/sitemap.xml');

        $xml = $sitemapService->generateXml($request);

        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        $this->assertStringContainsString('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', $xml);
        $this->assertStringContainsString('https://bridgeclubfribourg.ch/', $xml);
        $this->assertStringContainsString('https://bridgeclubfribourg.ch/lessons', $xml);

        $dom = new \DOMDocument();
        $this->assertTrue($dom->loadXML($xml), 'Sitemap XML must be well-formed');
    }
}
