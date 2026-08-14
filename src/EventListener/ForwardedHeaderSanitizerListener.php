<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 300)]
class ForwardedHeaderSanitizerListener
{
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        $proto = $request->headers->get('X-Forwarded-Proto', '');
        $port = $request->headers->get('X-Forwarded-Port', '');

        if ($this->isPlaceholder($proto)) {
            $proto = $this->resolveSchemeFromCfVisitor($request->headers->get('Cf-Visitor'));
            $request->headers->set('X-Forwarded-Proto', $proto);
        }

        if ($this->isPlaceholder($port)) {
            $request->headers->set('X-Forwarded-Port', $proto === 'https' ? '443' : '80');
        }
    }

    private function isPlaceholder(string $value): bool
    {
        return (bool) preg_match('/^\{[A-Za-z0-9_]+\}$/', trim($value));
    }

    private function resolveSchemeFromCfVisitor(?string $cfVisitorHeader): string
    {
        if ($cfVisitorHeader === null || $cfVisitorHeader === '') {
            return 'https';
        }

        $decoded = json_decode($cfVisitorHeader, true);
        if (!is_array($decoded)) {
            return 'https';
        }

        $scheme = strtolower((string) ($decoded['scheme'] ?? ''));

        return $scheme === 'http' ? 'http' : 'https';
    }
}
