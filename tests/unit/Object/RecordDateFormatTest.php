<?php

namespace airmoi\FileMaker\Tests\Unit\Object;

use airmoi\FileMaker\Tests\Support\FakeFileMaker;
use airmoi\FileMaker\Tests\Support\Fixtures;
use PHPUnit\Framework\TestCase;

/**
 * Dates were converted twice (Record::setField() then Add::setField()) on commit
 * @see https://github.com/airmoi/FileMaker/issues/35
 */
class RecordDateFormatTest extends TestCase
{
    private function fm(): FakeFileMaker
    {
        $fm = new FakeFileMaker('test', 'https://fms.example', 'admin', 'secret', ['dateFormat' => 'd/m/Y']);
        $fm->defaultCwpResponse = Fixtures::fmResultSet([], [
            7 => ['id' => 1, 'name' => 'foo', 'code' => 'x', 'date_field' => '12/25/2023', 'timestamp_field' => '12/25/2023 10:30:00'],
        ]);
        return $fm;
    }

    public function testCreateRecordCommitConvertsDatesOnce(): void
    {
        $fm = $this->fm();
        $record = $fm->createRecord('sample', [
            'name' => 'foo',
            'date_field' => '25/12/2023',
            'timestamp_field' => '25/12/2023 10:30:00',
        ]);

        $this->assertTrue($record->commit());

        $params = $fm->lastCwpParams();
        $this->assertArrayHasKey('-new', $params);
        $this->assertSame('12/25/2023', $params['date_field(1)']);
        $this->assertSame('12/25/2023 10:30:00', $params['timestamp_field(1)']);
        $this->assertSame('7', $record->getRecordId());
        $this->assertSame('25/12/2023', $record->getField('date_field'));
    }

    public function testAddCommandStillConvertsDates(): void
    {
        $fm = $this->fm();
        $fm->newAddCommand('sample', ['date_field' => '25/12/2023'])->execute();
        $this->assertSame('12/25/2023', $fm->lastCwpParams()['date_field(1)']);
    }

    public function testEditedRecordCommitConvertsDatesOnce(): void
    {
        $fm = $this->fm();
        $record = $fm->getRecordById('sample', 7);
        $record->setField('date_field', '31/01/2024');
        $record->commit();
        $this->assertSame('01/31/2024', $fm->lastCwpParams()['date_field(1)']);
    }
}
