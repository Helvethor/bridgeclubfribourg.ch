<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\EventListener\BotFirewallListener;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class SimpleTestCacheItem implements CacheItemInterface
{
    public function __construct(
        private string $key,
        private mixed $value = null,
        private bool $hit = false
    ) {}

    public function getKey(): string { return $this->key; }
    public function get(): mixed { return $this->value; }
    public function isHit(): bool { return $this->hit; }
    public function set(mixed $value): static { $this->value = $value; $this->hit = true; return $this; }
    public function expiresAt(?\DateTimeInterface $expiration): static { return $this; }
    public function expiresAfter(\DateInterval|int|null $time): static { return $this; }
}

class SimpleTestCachePool implements CacheItemPoolInterface
{
    /** @var array<string, SimpleTestCacheItem> */
    public array $items = [];

    public function getItem(string $key): CacheItemInterface
    {
        return $this->items[$key] ??= new SimpleTestCacheItem($key);
    }

    public function getItems(array $keys = []): iterable
    {
        $result = [];
        foreach ($keys as $k) {
            $result[$k] = $this->getItem($k);
        }
        return $result;
    }

    public function hasItem(string $key): bool { return isset($this->items[$key]) && $this->items[$key]->isHit(); }
    public function clear(): bool { $this->items = []; return true; }
    public function deleteItem(string $key): bool { unset($this->items[$key]); return true; }
    public function deleteItems(array $keys): bool { foreach ($keys as $k) { unset($this->items[$k]); } return true; }
    public function save(CacheItemInterface $item): bool { $this->items[$item->getKey()] = $item; return true; }
    public function saveDeferred(CacheItemInterface $item): bool { return $this->save($item); }
    public function commit(): bool { return true; }
}

class BotFirewallListenerTest extends TestCase
{
    public function testAllowsNormalTraffic(): void
    {
        $cachePool = new SimpleTestCachePool();
        $listener = new BotFirewallListener($cachePool);

        $kernel = new class implements HttpKernelInterface {
            public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): Response
            {
                return new Response();
            }
        };

        $request = Request::create('/');
        $request->server->set('REMOTE_ADDR', '198.51.100.10');
        $event = new RequestEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testBlocksBannedIp(): void
    {
        $cachePool = new SimpleTestCachePool();
        $banItem = new SimpleTestCacheItem('bot_fw_198_51_100_10_ban', true, true);
        $cachePool->save($banItem);

        $listener = new BotFirewallListener($cachePool);

        $kernel = new class implements HttpKernelInterface {
            public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): Response
            {
                return new Response();
            }
        };

        $request = Request::create('/');
        $request->server->set('REMOTE_ADDR', '198.51.100.10');
        $event = new RequestEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener->onKernelRequest($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }
}
