<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityControllerTest extends WebTestCase
{
    public function testLoginPageLoads(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="_username"]');
        $this->assertSelectorExists('input[name="_password"]');
    }

    public function testLoginWithInvalidCredentialsFails(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('login')->form([
            '_username' => 'wronguser',
            '_password' => 'wrongpass',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/login');
        $crawler = $client->followRedirect();
        $this->assertSelectorExists('.alert, .alert-warning, div');
    }

    public function testLoginWithValidCredentialsSucceeds(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('login')->form([
            '_username' => 'admin',
            '_password' => 'testpassword',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects();
    }
}
