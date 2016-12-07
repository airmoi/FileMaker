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
 * Class DateTimeHelper
 * Provide methods to convert date input/output
 * @package airmoi\FileMaker\Helpers
 *
 */
class DateFormat
{
    /**
     * @param string $value
     * @param string $inputFormat
     * @param string $outputFormat
     * @return string
     * @throws \Exception
     */
    public static function convert($value, $inputFormat=null, $outputFormat=null)
    {
        if (empty($value) || $inputFormat === null || $outputFormat === null) {
            return $value;
        }

        //Parse value to detect incorrect date format
        $parsedDate = date_parse_from_format($inputFormat, $value);
        if ($parsedDate['error_count'] || $parsedDate['warning_count']) {
            throw new \Exception('invalid date format');
        }

        $date = \DateTime::createFromFormat($inputFormat, $value);

        return $date->format($outputFormat);
    }

    public static function convertSearchCriteria($value)
    {
        //Handle common operators (<, >, =, ==, ...)
        return $value;
    }
}