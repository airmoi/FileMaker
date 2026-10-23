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

    /**
     * @see https://github.com/airmoi/FileMaker/issues/36
     */
    public function testAutoEnteredFieldsCanBeSkippedOnAdd(): void
    {
        $fm = $this->fm(['skipAutoEnteredValidation' => true]);
        $fm->newAddCommand('sample', ['name' => 'foo', 'code' => 'x'])->execute();
        $this->assertArrayHasKey('-new', $fm->lastCwpParams());
        $this->assertArrayNotHasKey('id(1)', $fm->lastCwpParams());
    }

    /**
     * @see https://github.com/airmoi/FileMaker/issues/36
     */
    public function testAutoEnteredFieldsCanBeSkippedOnRecordCommit(): void
    {
        $fm = $this->fm(['skipAutoEnteredValidation' => true]);
        $record = $fm->createRecord('sample', ['name' => 'foo', 'code' => 'x']);
        $this->assertTrue($record->validate());
        $this->assertTrue($record->commit());
        $this->assertSame('6', $record->getRecordId());
    }

    /**
     * @see https://github.com/airmoi/FileMaker/issues/36
     */
    public function testAutoEnteredFieldsCanBeSkippedForSingleFieldValidation(): void
    {
        $fm = $this->fm(['skipAutoEnteredValidation' => true]);
        $this->assertTrue($fm->newAddCommand('sample')->validate('id'));
    }

    /**
     * @see https://github.com/airmoi/FileMaker/issues/36
     */
    public function testAutoEnteredFieldsStillValidatedByDefault(): void
    {
        $fm = $this->fm();
        try {
            $fm->newAddCommand('sample', ['name' => 'foo', 'code' => 'x'])->execute();
            $this->fail('Validation exception expected');
        } catch (FileMakerValidationException $e) {
            $this->assertSame(['id'], $this->errorFields($e));
        }
    }

    /**
     * @see https://github.com/airmoi/FileMaker/issues/36
     */
    public function testProvidedValuesOfAutoEnteredFieldsAreValidated(): void
    {
        $fm = $this->fm(['skipAutoEnteredValidation' => true]);
        $fm->getLayout('sample')->getField('id')->validationRules[\airmoi\FileMaker\FileMaker::RULE_NUMERICONLY] = true;
        try {
            $fm->newAddCommand('sample', ['id' => 'abc', 'name' => 'foo', 'code' => 'x'])->execute();
            $this->fail('Validation exception expected');
        } catch (FileMakerValidationException $e) {
            $this->assertSame(['id'], $this->errorFields($e));
        }
    }
}
