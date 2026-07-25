<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::TERMINATE)]
class PDFListener
{
    public function onKernelTerminate(TerminateEvent $event): void
    {
        // Post-response PDF generation logic goes here.
    }
}
