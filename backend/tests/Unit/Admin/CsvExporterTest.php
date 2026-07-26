<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

use App\Admin\Support\CsvExporter;
use PHPUnit\Framework\TestCase;

final class CsvExporterTest extends TestCase
{
    private CsvExporter $csv;

    protected function setUp(): void
    {
        $this->csv = new CsvExporter();
    }

    public function testRendersHeaderAndRowsByKeys(): void
    {
        $out = $this->csv->toCsv(
            ['id', 'name'],
            [['id' => 1, 'name' => 'Ann'], ['id' => 2, 'name' => 'Bob']],
            ['id', 'name']
        );

        $lines = array_values(array_filter(explode("\n", trim($out))));
        self::assertSame('id,name', trim($lines[0]));
        self::assertSame('1,Ann', trim($lines[1]));
        self::assertSame('2,Bob', trim($lines[2]));
    }

    public function testNeutralisesFormulaInjection(): void
    {
        $out = $this->csv->toCsv(['v'], [['v' => '=SUM(A1:A2)']], ['v']);

        self::assertStringContainsString("'=SUM(A1:A2)", $out);
    }
}
