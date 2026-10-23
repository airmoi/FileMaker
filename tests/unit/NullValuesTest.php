<?php

namespace airmoi\FileMaker\Tests\Unit;

use airmoi\FileMaker\Helpers\DataApi;
use airmoi\FileMaker\Tests\Support\FakeFileMaker;
use airmoi\FileMaker\Tests\Support\Fixtures;
use PHPUnit\Framework\TestCase;

/**
 * Passing null values must not trigger "Passing null to parameter" deprecations
 * @see https://github.com/airmoi/FileMaker/issues/48
 */
class NullValuesTest extends TestCase
{
    private function cwp(): FakeFileMaker
    {
        $fm = new FakeFileMaker('test', 'https://fms.example', 'admin', 'secret');
        $fm->defaultCwpResponse = Fixtures::fmResultSet([], [1 => ['id' => 1, 'name' => 'foo', 'code' => 'x']]);
        return $fm;
    }

    public function testAddCommandWithNullValues(): void
    {
        $fm = $this->cwp();
        $command = $fm->newAddCommand('sample', ['name' => null, 'note' => null, 'date_field' => null]);
        $command->setScript('script', null);
        $command->execute();

        $params = $fm->lastCwpParams();
        $this->assertArrayHasKey('name(1)', $params);
        $this->assertNull($params['name(1)']);
    }

    public function testEditCommandWithNullValues(): void
    {
        $fm = $this->cwp();
        $fm->newEditCommand('sample', 1, ['note' => null, 'timestamp_field' => null])->execute();
        $this->assertSame(1, $fm->lastCwpParams()['-recid']);
    }

    public function testFindWithNullCriterion(): void
    {
        $fm = $this->cwp();
        $find = $fm->newFindCommand('sample');
        $find->addFindCriterion('name', null);
        $find->addSortRule('name', 1, null);
        $find->execute();
        $this->assertArrayHasKey('-find', $fm->lastCwpParams());
    }

    public function testRecordWithNullValues(): void
    {
        $fm = $this->cwp();
        $record = $fm->createRecord('sample', ['name' => null, 'note' => null]);
        $record->setField('date_field', null);
        $this->assertSame('', (string) $record->getField('note'));
        $record->commit();
    }

    public function testCwpQueryEncodesNullValues(): void
    {
        $fm = new class ('test', 'https://fms.example') extends FakeFileMaker {
            public function query(array $params): array
            {
                return $this->buildCwpQuery($params);
            }
        };
        [$query, $footprint] = $fm->query(['-db' => 'test', 'name(1)' => null, 'id' => 5, '-find' => true]);
        $this->assertSame(['-db=test', 'name%281%29=', 'id=5', '-find'], $query);
        $this->assertSame(['-db=test', 'name(1)=', 'id=5', '-find'], $footprint);
    }

    public function testDataApiNewRecordWithoutFieldData(): void
    {
        $query = DataApi::prepareQuery(['-db' => 'test', '-lay' => 'sample', '-new' => true]);
        $this->assertSame('POST', $query['method']);
        $this->assertSame([], $query['body']);
    }

    public function testDataApiNullFieldValues(): void
    {
        $query = DataApi::prepareQuery(['-db' => 'test', '-lay' => 'sample', '-new' => true, 'name(1)' => null]);
        $this->assertSame(['fieldData' => ['name(1)' => '']], $query['body']);
    }
}
