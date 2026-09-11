<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\InMemoryUser;

class AdminAccessControlTest extends WebTestCase
{
    #[DataProvider('adminRoutesProvider')]
    public function testAdminRoutesRequireAuthentication(string $url): void
    {
        $client = static::createClient();
        $client->request('GET', $url);

        $this->assertResponseRedirects('/login');
    }

    #[DataProvider('adminRoutesProvider')]
    public function testAdminRoutesAccessibleWhenLoggedIn(string $url): void
    {
        $client = static::createClient();
        $userProvider = static::getContainer()->get('security.user.provider.concrete.admin_user');
        $user = $userProvider->loadUserByIdentifier('admin');
        $client->loginUser($user);

        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();
    }

    public static function adminRoutesProvider(): array
    {
        return [
            'admin_turnament' => ['/admin/turnament'],
            'admin_news' => ['/admin/news'],
            'admin_lessons' => ['/admin/lessons'],
            'admin_online_game' => ['/admin/online_game'],
            'admin_registrations' => ['/admin/registrations'],
        ];
    }
}
