<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::RESPONSE)]
class XRobotsTagListener
{
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $path = $event->getRequest()->getPathInfo();
        if (!$this->shouldSetXRobotsTag($path)) {
            return;
        }

        $response = $event->getResponse();
        if ($response->headers->has('X-Robots-Tag')) {
            return;
        }

        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }

    private function shouldSetXRobotsTag(string $path): bool
    {
        return $path === '/pdf'
            || str_starts_with($path, '/pdf/')
            || $path === '/turnament'
            || str_starts_with($path, '/turnament/');
    }
}
