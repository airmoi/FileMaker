<?php

namespace airmoi\FileMaker\Tests\Unit\Command;

use airmoi\FileMaker\FileMakerValidationException;
use airmoi\FileMaker\Tests\Support\FakeFileMaker;
use airmoi\FileMaker\Tests\Support\Fixtures;
use PHPUnit\Framework\TestCase;

class PrevalidationTest extends TestCase
{
    private function fm(array $options = []): FakeFileMaker
    {
        $fm = new FakeFileMaker('test', 'https://fms.example', 'admin', 'secret', ['prevalidate' => true] + $options);
        $fm->defaultCwpResponse = Fixtures::fmResultSet([], [
            6 => ['id' => 6, 'name' => 'foo', 'code' => 'x', 'note' => ''],
        ]);
        return $fm;
    }

    private function errorFields(FileMakerValidationException $e): array
    {
        return array_map(fn($error) => $error[0]->getName(), $e->getErrors());
    }

    /**
     * @see https://github.com/airmoi/FileMaker/issues/19
     */
    public function testEditCommandOnlyValidatesModifiedFields(): void
    {
        $fm = $this->fm();
        $fm->newEditCommand('sample', 6, ['note' => 'bar'])->execute();
        $this->assertSame('bar', $fm->lastCwpParams()['note(1)']);
    }

    /**
     * @see https://github.com/airmoi/FileMaker/issues/19
     */
    public function testEditCommandStillValidatesModifiedFields(): void
    {
        $fm = $this->fm();
        try {
            $fm->newEditCommand('sample', 6, ['name' => '', 'note' => 'bar'])->execute();
            $this->fail('Validation exception expected');
        } catch (FileMakerValidationException $e) {
            $this->assertSame(['name'], $this->errorFields($e));
        }
    }

    /**
     * @see https://github.com/airmoi/FileMaker/issues/19
     */
    public function testRecordCommitOnlyValidatesModifiedFields(): void
    {
        $fm = $this->fm();
        $record = $fm->getRecordById('sample', 6);
        $record->setField('note', 'bar');
        $this->assertTrue($record->commit());
        $this->assertSame('bar', $fm->lastCwpParams()['note(1)']);
        $this->assertArrayNotHasKey('name(1)', $fm->lastCwpParams());
    }

    public function testAddCommandValidatesAllFields(): void
    {
        $fm = $this->fm();
        try {
            $fm->newAddCommand('sample', ['note' => 'bar'])->execute();
            $this->fail('Validation exception expected');
        } catch (FileMakerValidationException $e) {
            $this->assertSame(['id', 'name', 'code'], $this->errorFields($e));
        }
    }
}
