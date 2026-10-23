<?php

namespace airmoi\FileMaker\Tests\Unit\Parser;

use airmoi\FileMaker\Object\Layout;
use airmoi\FileMaker\Object\Record;
use airmoi\FileMaker\Tests\Support\FakeFileMaker;
use airmoi\FileMaker\Tests\Support\Fixtures;
use PHPUnit\Framework\TestCase;

class FMResultSetTest extends TestCase
{
    public function testGetLayoutParsesFieldDefinitions(): void
    {
        $fm = new FakeFileMaker('test', 'https://fms.example', 'admin', 'secret');
        $fm->queueCwp(Fixtures::fmResultSet());

        $layout = $fm->getLayout('sample');

        $this->assertInstanceOf(Layout::class, $layout);
        $this->assertSame('sample', $layout->getName());
        $this->assertTrue($layout->getField('id')->isAutoEntered());
        $this->assertSame('date', $layout->getField('date_field')->getResult());
    }

    public function testFindReturnsRecords(): void
    {
        $fm = new FakeFileMaker('test', 'https://fms.example', 'admin', 'secret');
        $fm->queueCwp(Fixtures::fmResultSet([], [
            12 => ['id' => 1, 'name' => 'Foo & bar', 'date_field' => '02/12/2016'],
        ]));

        $result = $fm->newFindAllCommand('sample')->execute();
        $record = $result->getFirstRecord();

        $this->assertInstanceOf(Record::class, $record);
        $this->assertSame('12', $record->getRecordId());
        $this->assertSame('Foo & bar', $record->getField('name', 0, true));
        $this->assertSame('02/12/2016', $record->getField('date_field'));
    }
}
