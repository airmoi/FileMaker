<?php

namespace airmoi\FileMaker\Tests\Unit;

use airmoi\FileMaker\FileMaker;
use airmoi\FileMaker\FileMakerValidationException;
use airmoi\FileMaker\Object\Field;
use airmoi\FileMaker\Object\Layout;
use airmoi\FileMaker\Tests\Support\FakeFileMaker;
use PHPUnit\Framework\TestCase;

class ValidationTest extends TestCase
{
    public function testMessageOnlyContainsValidationErrors(): void
    {
        $fm = new FakeFileMaker('test');
        $exception = new FileMakerValidationException($fm);
        $field = new Field(new Layout($fm));
        $field->name = 'date';
        $exception->addError($field, FileMaker::RULE_DATE_FIELD, 'foo');
        $exception->addError($field, FileMaker::RULE_NOTEMPTY, null);

        $this->assertSame(
            "Please enter a valid date for field date\nField date is required",
            $exception->getMessage()
        );
    }

    public function testFormatChecksAcceptNull(): void
    {
        $field = new Field(new Layout(new FakeFileMaker('test')));
        $this->assertSame(0, $field->checkDateFormat(null));
        $this->assertSame(0, $field->checkTimeFormat(null));
        $this->assertSame(0, $field->checkTimeStampFormat(null));
        $this->assertSame(0, $field->checkTimeStampFormatFourDigitYear(null));
        $this->assertSame(1, $field->checkDateFormat('12/25/2023'));
    }

    public function testHasValidationRule(): void
    {
        $fm = new FakeFileMaker('test');
        $fm->queueCwp(\airmoi\FileMaker\Tests\Support\Fixtures::fmResultSet([
            'text_field' => ['not-empty' => 'yes', 'max-characters' => '50'],
            'note' => [],
        ]));
        $layout = $fm->getLayout('sample');

        $field = $layout->getField('text_field');
        $this->assertTrue($field->hasValidationRule(FileMaker::RULE_NOTEMPTY));
        $this->assertTrue($field->hasValidationRule(FileMaker::RULE_MAXCHARACTERS));
        $this->assertFalse($field->hasValidationRule(FileMaker::RULE_NUMERICONLY));
        $this->assertSame(50, $field->getMaxCharacters());
        $this->assertFalse($layout->getField('note')->hasValidationRule(FileMaker::RULE_NOTEMPTY));
    }

    public function testErrorStringWithNullCode(): void
    {
        $exception = new \airmoi\FileMaker\FileMakerException(new FakeFileMaker('test'), 'message');
        $this->assertSame('Unknown error', $exception->getErrorString(null));
        $this->assertSame('No error', $exception->getErrorString(0));
    }
}
