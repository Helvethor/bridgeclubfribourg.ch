<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Twig\FrenchDateExtension;
use PHPUnit\Framework\TestCase;

class FrenchDateExtensionTest extends TestCase
{
    private FrenchDateExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new FrenchDateExtension();
    }

    public function testGetFilters(): void
    {
        $filters = $this->extension->getFilters();
        $this->assertCount(3, $filters);

        $filterNames = array_map(static fn($f) => $f->getName(), $filters);
        $this->assertContains('frenchDate', $filterNames);
        $this->assertContains('frenchMonth', $filterNames);
        $this->assertContains('frenchDow', $filterNames);
    }

    public function testFrenchMonthFilter(): void
    {
        $january = new \DateTime('2026-01-15');
        $this->assertSame('Janvier', $this->extension->frenchMonthFilter($january));

        $july = new \DateTime('2026-07-20');
        $this->assertSame('Juillet', $this->extension->frenchMonthFilter($july));

        $december = new \DateTime('2026-12-31');
        $this->assertSame('Décembre', $this->extension->frenchMonthFilter($december));
    }

    public function testFrenchDowFilter(): void
    {
        $this->assertSame('lundi', $this->extension->frenchDowFilter('monday'));
        $this->assertSame('mardi', $this->extension->frenchDowFilter('tuesday'));
        $this->assertSame('mercredi', $this->extension->frenchDowFilter('Wednesday'));
        $this->assertSame('jeudi', $this->extension->frenchDowFilter('THURSDAY'));
        $this->assertSame('vendredi', $this->extension->frenchDowFilter('friday'));
        $this->assertSame('samedi', $this->extension->frenchDowFilter('saturday'));
        $this->assertSame('dimanche', $this->extension->frenchDowFilter('sunday'));
        $this->assertSame('unknown', $this->extension->frenchDowFilter('unknown'));
    }

    public function testFrenchDateFilter(): void
    {
        $date = new \DateTime('2026-05-18 14:30:00');
        $formatted = $this->extension->frenchDateFilter($date, 'full');
        $this->assertNotEmpty($formatted);
        $this->assertStringContainsString('mai', strtolower($formatted));
        $this->assertStringContainsString('2026', $formatted);
    }
}
