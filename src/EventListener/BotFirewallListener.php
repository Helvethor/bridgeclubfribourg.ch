<?php

declare(strict_types=1);

namespace App\EventListener;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 100)]
class BotFirewallListener
{
    private const DAY_THRESHOLD = 600;
    private const BAN_1_DAY_TTL = 86400;
    private const BAN_7_DAY_TTL = 7 * 86400;
    // Keep ban count for 30 days so repeat offenders within a month get the long ban.
    private const BAN_COUNT_TTL = 30 * 86400;

    public function __construct(private readonly CacheItemPoolInterface $cachePool)
    {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $ip = $this->getRealIp($event->getRequest());
        if ($ip === null) {
            return;
        }

        // Cache key safe for all IP formats (IPv4 dots, IPv6 colons).
        $key = 'bot_fw_' . str_replace(['.', ':'], '_', $ip);

        $banItem = $this->cachePool->getItem($key . '_ban');
        if ($banItem->isHit()) {
            $event->setResponse(new Response('Forbidden', Response::HTTP_FORBIDDEN));
            return;
        }

        $dayKey = $key . '_' . date('Ymd');
        $countItem = $this->cachePool->getItem($dayKey);
        $count = $countItem->isHit() ? (int) $countItem->get() : 0;
        $count++;

        $countItem->set($count)->expiresAfter(48 * 3600);
        $this->cachePool->save($countItem);

        if ($count >= self::DAY_THRESHOLD) {
            $this->ban($key, $ip, $count);
        }
    }

    private function ban(string $key, string $ip, int $triggerCount): void
    {
        $banCountItem = $this->cachePool->getItem($key . '_ban_count');
        $banCount = $banCountItem->isHit() ? (int) $banCountItem->get() : 0;
        $banCount++;
        $banCountItem->set($banCount)->expiresAfter(self::BAN_COUNT_TTL);
        $this->cachePool->save($banCountItem);

        // Second ban onwards within 30 days → 7-day ban.
        $ttl = $banCount >= 2 ? self::BAN_7_DAY_TTL : self::BAN_1_DAY_TTL;

        $banItem = $this->cachePool->getItem($key . '_ban');
        $banItem->set(true)->expiresAfter($ttl);
        $this->cachePool->save($banItem);

        error_log(sprintf(
            '[BotFirewall] banned ip=%s ttl_days=%d ban_number=%d trigger_count=%d',
            $ip,
            intdiv($ttl, 86400),
            $banCount,
            $triggerCount,
        ));
    }

    private function getRealIp(Request $request): ?string
    {
        // Cloudflare sets this header to the original client IP.
        $cfIp = $request->headers->get('Cf-Connecting-Ip');
        if ($cfIp !== null && filter_var($cfIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $cfIp;
        }

        // Fallback for direct connections (no Cloudflare).
        $remoteAddr = $request->server->get('REMOTE_ADDR', '');
        if (filter_var($remoteAddr, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $remoteAddr;
        }

        return null;
    }
}
