<?php
/**
 * FileMaker API for PHP
 *
 * @package FileMaker
 *
 * @copyright Copyright (c) 2016 by 1-more-thing (http://1-more-thing.com) All rights reserved.
 * @license BSD
 */

namespace airmoi\FileMaker\Helpers;

/**
 * Class DateFormatTest
 *
 * @package airmoi\FileMaker\Helpers
 */
class DateFormatTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    public function testConvertDefaultFormat()
    {
        $this->assertEquals('12/02/2016', DateFormat::convert('02/12/2016', 'm/d/Y', 'd/m/Y'));
        $this->assertEquals('2016-02-12', DateFormat::convert('02/12/2016', 'm/d/Y', 'Y-m-d'));
        $this->assertEquals(
            '12/02/2016 00:00:00',
            DateFormat::convert('02/12/2016 00:00:00', 'm/d/Y H:i:s', 'd/m/Y H:i:s')
        );

        $this->expectException(\Exception::class);
        DateFormat::convert('16/02/2016', 'm/d/Y', 'd/m/Y H:i:s');
    }

    public function testConvertISOFormat()
    {
        $this->assertEquals('12/02/2016', DateFormat::convert('2016-02-12', 'Y-m-d', 'd/m/Y'));
        $this->assertEquals('02/12/2016', DateFormat::convert('2016-02-12', 'Y-m-d', 'm/d/Y'));
        $this->assertEquals(
            '12/02/2016 00:00:00',
            DateFormat::convert('2016-02-12 00:00:00', 'Y-m-d H:i:s', 'd/m/Y H:i:s')
        );

        $this->expectException(\Exception::class);
        DateFormat::convert('16/02/2016', 'Y-m-d', 'd/m/Y H:i:s');
    }

    public function testSanitizeDateSearchString()
    {
        $this->assertEquals('12/02/2016', DateFormat::sanitizeDateSearchString("=12/02/2016"));
        $this->assertEquals('2016-02-12', DateFormat::sanitizeDateSearchString("==2016-02-12"));
        $this->assertEquals('02/*/2016', DateFormat::sanitizeDateSearchString("==02/@/2016"));
        $this->assertEquals('02/*/2016', DateFormat::sanitizeDateSearchString("==02/@@/2016"));
        $this->assertEquals('*/02/2016', DateFormat::sanitizeDateSearchString("==#/02/2016"));
        $this->assertEquals('*/02/2016', DateFormat::sanitizeDateSearchString("==##/02/2016"));
        $this->assertEquals('12/02/2016', DateFormat::sanitizeDateSearchString("~12/02/2016"));
        $this->assertEquals(
            '12/02/2016...12/06/2016',
            DateFormat::sanitizeDateSearchString("=12/02/2016...12/06/2016")
        );
    }

    public function testConvertSearchCriteria()
    {
        $this->assertEquals('12/02/2016', DateFormat::convertSearchCriteria("12/02/2016"));
        $this->assertEquals('12/02/2016', DateFormat::convertSearchCriteria("02/12/2016", "m/d/Y", 'd/m/Y'));
        $this->assertEquals(
            '02/12/2016...06/12/2016',
            DateFormat::convertSearchCriteria("12/02/2016...12/06/2016", "d/m/Y", 'm/d/Y')
        );
        $this->assertEquals(
            '02/12/2016...06/12/2016',
            DateFormat::convertSearchCriteria("2016-02-12...2016-06-12", "Y-m-d", 'm/d/Y')
        );
        $this->assertEquals(
            '06/*/2016...12/*/2016',
            DateFormat::convertSearchCriteria("2016-06-*...2016-12-*", "Y-m-d", 'm/d/Y')
        );
        $this->assertEquals(
            '>02/12/2016',
            DateFormat::convertSearchCriteria(">2016-02-12", "Y-m-d", 'm/d/Y')
        );
        $this->assertEquals(
            '<02/12/2016',
            DateFormat::convertSearchCriteria("<2016-02-12", "Y-m-d", 'm/d/Y')
        );
        $this->assertEquals(
            '≤02/12/2016',
            DateFormat::convertSearchCriteria("≤2016-02-12", "Y-m-d", 'm/d/Y')
        );
        $this->assertEquals(
            '<=02/12/2016',
            DateFormat::convertSearchCriteria("<=2016-02-12", "Y-m-d", 'm/d/Y')
        );
        $this->assertEquals(
            '>=02/12/2016',
            DateFormat::convertSearchCriteria(">=2016-02-12", "Y-m-d", 'm/d/Y')
        );
        $this->assertEquals(
            '>=02/*/2016',
            DateFormat::convertSearchCriteria(">=2016-02-*", "Y-m-d", 'm/d/Y')
        );

        $this->assertEquals(
            '>=02/*/2016',
            DateFormat::convertSearchCriteria(">=2016-02-* 12:01:00", "Y-m-d H:i:s", 'm/d/Y')
        );

        $this->assertEquals(
            '>=02/12/2016 *:*:*',
            DateFormat::convertSearchCriteria(">=2016-02-12", "Y-m-d", 'm/d/Y H:i:s')
        );


        /*$this->assertEquals(
            '>=02/ /2016',
            DateFormat::convertSearchCriteria(">=2016-02-*", "Y-m-d", 'D d F Y')
        );*/
    }
}
