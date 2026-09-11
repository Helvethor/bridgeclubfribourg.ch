<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\EventListener\ForwardedHeaderSanitizerListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ForwardedHeaderSanitizerListenerTest extends TestCase
{
    private ForwardedHeaderSanitizerListener $listener;
    private HttpKernelInterface $kernel;

    protected function setUp(): void
    {
        $this->listener = new ForwardedHeaderSanitizerListener();
        $this->kernel = new class implements HttpKernelInterface {
            public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): \Symfony\Component\HttpFoundation\Response
            {
                return new \Symfony\Component\HttpFoundation\Response();
            }
        };
    }

    public function testIgnoresSubRequests(): void
    {
        $request = new Request();
        $request->headers->set('X-Forwarded-Proto', '{proto}');
        $event = new RequestEvent($this->kernel, $request, HttpKernelInterface::SUB_REQUEST);

        $this->listener->onKernelRequest($event);

        $this->assertSame('{proto}', $request->headers->get('X-Forwarded-Proto'));
    }

    public function testSanitizesProtoPlaceholderWithHttpsFallback(): void
    {
        $request = new Request();
        $request->headers->set('X-Forwarded-Proto', '{proto}');
        $request->headers->set('X-Forwarded-Port', '{port}');
        $event = new RequestEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->listener->onKernelRequest($event);

        $this->assertSame('https', $request->headers->get('X-Forwarded-Proto'));
        $this->assertSame('443', $request->headers->get('X-Forwarded-Port'));
    }

    public function testSanitizesProtoFromCfVisitorHeader(): void
    {
        $request = new Request();
        $request->headers->set('X-Forwarded-Proto', '{proto}');
        $request->headers->set('X-Forwarded-Port', '{port}');
        $request->headers->set('Cf-Visitor', '{"scheme":"http"}');
        $event = new RequestEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->listener->onKernelRequest($event);

        $this->assertSame('http', $request->headers->get('X-Forwarded-Proto'));
        $this->assertSame('80', $request->headers->get('X-Forwarded-Port'));
    }

    public function testPreservesValidHeaders(): void
    {
        $request = new Request();
        $request->headers->set('X-Forwarded-Proto', 'https');
        $request->headers->set('X-Forwarded-Port', '8443');
        $event = new RequestEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->listener->onKernelRequest($event);

        $this->assertSame('https', $request->headers->get('X-Forwarded-Proto'));
        $this->assertSame('8443', $request->headers->get('X-Forwarded-Port'));
    }
}
