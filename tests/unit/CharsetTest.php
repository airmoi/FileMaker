<?php

namespace airmoi\FileMaker\Tests\Unit;

use airmoi\FileMaker\Tests\Support\FakeFileMaker;
use airmoi\FileMaker\Tests\Support\Fixtures;
use PHPUnit\Framework\TestCase;

/**
 * @see https://github.com/airmoi/FileMaker/issues/52
 */
class CharsetTest extends TestCase
{
    public function testUtf8CharsetLeavesValuesUntouched(): void
    {
        $fm = new FakeFileMaker('test');
        $this->assertSame('Ã©tÃ©', $fm->toOutputCharset('Ã©tÃ©'));
        $this->assertSame('été', $fm->toInputCharset('été'));
        $this->assertSame('', $fm->toInputCharset(null));
    }

    public function testIsoCharsetConvertsOutput(): void
    {
        $fm = new FakeFileMaker('test', null, null, null, ['charset' => 'ISO-8859-1']);
        $this->assertSame("\xE9t\xE9", $fm->toOutputCharset('été'));
        $this->assertSame(["\xE9" => ["\xE9t\xE9", 1]], $fm->toOutputCharset(['é' => ['été', 1]]));
    }

    public function testIsoCharsetConvertsInput(): void
    {
        $fm = new FakeFileMaker('test', null, null, null, ['charset' => 'ISO-8859-1']);
        $this->assertSame('été', $fm->toInputCharset("\xE9t\xE9"));
    }

    public function testIsoCharsetParsesServerResponse(): void
    {
        $fm = new FakeFileMaker('test', null, null, null, ['charset' => 'ISO-8859-1']);
        $fm->queueCwp(Fixtures::fmResultSet([], [1 => ['name' => 'été']]));

        $record = $fm->newFindAllCommand('sample')->execute()->getFirstRecord();
        $this->assertSame("\xE9t\xE9", $record->getField('name', 0, true));
    }
}
