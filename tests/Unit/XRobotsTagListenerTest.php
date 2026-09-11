<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\EventListener\XRobotsTagListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class XRobotsTagListenerTest extends TestCase
{
    private XRobotsTagListener $listener;
    private HttpKernelInterface $kernel;

    protected function setUp(): void
    {
        $this->listener = new XRobotsTagListener();
        $this->kernel = new class implements HttpKernelInterface {
            public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): Response
            {
                return new Response();
            }
        };
    }

    public function testSetsHeaderOnTurnamentRoute(): void
    {
        $request = Request::create('/turnament/registrations');
        $response = new Response();
        $event = new ResponseEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $this->listener->onKernelResponse($event);

        $this->assertTrue($response->headers->has('X-Robots-Tag'));
        $this->assertSame('noindex, nofollow, noarchive', $response->headers->get('X-Robots-Tag'));
    }

    public function testSetsHeaderOnPdfRoute(): void
    {
        $request = Request::create('/pdf/render');
        $response = new Response();
        $event = new ResponseEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $this->listener->onKernelResponse($event);

        $this->assertTrue($response->headers->has('X-Robots-Tag'));
    }

    public function testDoesNotSetHeaderOnPublicPages(): void
    {
        $request = Request::create('/home');
        $response = new Response();
        $event = new ResponseEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $this->listener->onKernelResponse($event);

        $this->assertFalse($response->headers->has('X-Robots-Tag'));
    }
}
