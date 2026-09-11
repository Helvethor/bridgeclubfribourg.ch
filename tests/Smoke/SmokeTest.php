<?php

declare(strict_types=1);

namespace App\Tests\Smoke;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SmokeTest extends WebTestCase
{
    #[DataProvider('publicUrlsProvider')]
    public function testPublicPageIsSuccessful(string $url): void
    {
        $client = static::createClient();
        $client->request('GET', $url);

        $this->assertResponseIsSuccessful(sprintf('URL "%s" returned error status code: %d', $url, $client->getResponse()->getStatusCode()));
    }

    public static function publicUrlsProvider(): array
    {
        return [
            'homepage_root' => ['/'],
            'homepage_home' => ['/home'],
            'lessons' => ['/lessons'],
            'robots_txt' => ['/robots.txt'],
            'sitemap_xml' => ['/sitemap.xml'],
            'login' => ['/login'],
            'turnament_calendar_default' => ['/turnament'],
        ];
    }
}
