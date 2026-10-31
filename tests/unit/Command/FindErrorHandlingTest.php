<?php

namespace airmoi\FileMaker\Tests\Unit\Command;

use airmoi\FileMaker\FileMakerException;
use airmoi\FileMaker\Tests\Support\FakeFileMaker;
use airmoi\FileMaker\Tests\Support\Fixtures;
use PHPUnit\Framework\TestCase;

class FindErrorHandlingTest extends TestCase
{
    private function executeFindCommand(string $commandName)
    {
        $fm = new FakeFileMaker('test', 'https://fms.example', 'admin', 'secret', [
            'errorHandling' => 'default',
        ]);
        $fm->queueCwp(Fixtures::fmResultSet([], [], 101));

        $command = match ($commandName) {
            'find' => $fm->newFindCommand('sample'),
            'findAll' => $fm->newFindAllCommand('sample'),
            'compoundFind' => $fm->newCompoundFindCommand('sample'),
        };

        return $command->execute();
    }

    public function testFindReturnsParserErrors(): void
    {
        $result = $this->executeFindCommand('find');

        $this->assertInstanceOf(FileMakerException::class, $result);
        $this->assertSame(101, $result->getCode());
    }

    public function testFindAllReturnsParserErrors(): void
    {
        $result = $this->executeFindCommand('findAll');

        $this->assertInstanceOf(FileMakerException::class, $result);
        $this->assertSame(101, $result->getCode());
    }

    public function testCompoundFindReturnsParserErrors(): void
    {
        $result = $this->executeFindCommand('compoundFind');

        $this->assertInstanceOf(FileMakerException::class, $result);
        $this->assertSame(101, $result->getCode());
    }
}
