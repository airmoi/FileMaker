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
    public static $omitOperatorsPattern = [
        '/^=*/' => null,
        '/[#|@]+/' => "*",
        //'/#/' => "*",
        '/~/' => null
    ];

    public static $byPassOperators = ['!', '?', ];
    /**
     * @param string $value
     * @param string $inputFormat
     * @param string $outputFormat
     * @return string
     * @throws \Exception
     */
    public static function convert($value, $inputFormat = null, $outputFormat = null)
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

    public static function convertSearchCriteria($value, $inputFormat = null, $outputFormat = null)
    {
        if (empty($value)
            || in_array($value, self::$byPassOperators)
            || $inputFormat == null
            || $outputFormat == null
        ) {
            return $value;
        }

        $value = self::sanitizeDateSearchString($value);

        $inputRegExp = '#' . self::dateFormatToRegex($inputFormat) . '#';

        //$regex = "#[<|>|≤|≥|<=|>=]?($inputRegExp)\.{0}|\.{3}($inputRegExp)?#";
        $value = preg_replace_callback(
            $inputRegExp,
            function ($matches) use ($inputFormat, $outputFormat) {
                return self::convertWithWildCards($matches[0], $inputFormat, $outputFormat);
            },
            $value
        );

        return $value;
    }

    public static function sanitizeDateSearchString($value)
    {
        foreach (self::$omitOperatorsPattern as $pattern => $replacement) {
            $value = preg_replace($pattern, $replacement, $value);
        }
        return $value;
    }

    public static function dateFormatToRegex($format)
    {
        $keys = array(
            'Y' => array('year', '\d{4}|\*'),
            'y' => array('year', '\d{2}|\*'),
            'm' => array('month', '\d{2}|\*'),
            'n' => array('month', '\d{1,2}|\*'),
            //'M' => array('month', '[A-Z][a-z]{3}'),
            //'F' => array('month', '[A-Z][a-z]{2,8}'),
            'd' => array('day', '\d{2}|\*'),
            'j' => array('day', '\d{1,2}|\*'),
            //'D' => array('day', '[A-Z][a-z]{2}'),
            //'l' => array('day', '[A-Z][a-z]{6,9}'),
            'u' => array('hour', '\d{1,6}'),
            'h' => array('hour', '\d{2}|\*'),
            'H' => array('hour', '\d{2}|\*'),
            'g' => array('hour', '\d{1,2}|\*'),
            'G' => array('hour', '\d{1,2}|\*'),
            'i' => array('minute', '\d{2}|\*'),
            's' => array('second', '\d{2}|\*')
        );

        // convert format string to regex
        $regex = '';
        $chars = str_split($format);
        foreach ($chars as $n => $char) {
            $lastChar = isset($chars[$n - 1]) ? $chars[$n - 1] : '';
            $skipCurrent = '\\' == $lastChar;
            if (!$skipCurrent && isset($keys[$char])) {
                $regex .= '(?P<' . $keys[$char][0] . '>' . $keys[$char][1] . ')';
            } elseif ('\\' == $char) {
                $regex .= $char;
            } else {
                $regex .= preg_quote($char);
            }
        }
        return $regex;
    }

    public static function convertWithWildCards($value, $inputFormat, $outputFormat)
    {
        $inputRegex = "#" . self::dateFormatToRegex($inputFormat) . "#";
        preg_match($inputRegex, $value, $parsedDate);

        $keys = array(
            'Y' => array('year', '%04d'),
            'y' => array('year', '%02d'),
            'm' => array('month', '%02d'),
            'n' => array('month', '%02d'),
            //'M' => array('month', '%3s'),
            //'F' => array('month', '%8s'),
            'd' => array('day', '%02d'),
            'j' => array('day', '%02d'),
            //'D' => array('day', '%2s'),
            //'l' => array('day', '%9s'),
            //'u' => array('hour', '%06d'),
            'h' => array('hour', '%02d'),
            'H' => array('hour', '%02d'),
            'g' => array('hour', '%02d'),
            'G' => array('hour', '%02d'),
            'i' => array('minute', '%02d'),
            's' => array('second', '%02d')
        );

        //convert to output format
        $string = '';
        $chars = str_split($outputFormat);
        foreach ($chars as $char) {
            if (isset($keys[$char])) {
                $val = @$parsedDate[$keys[$char][0]];
                $format = $keys[$char][1];
                $string .= $val == "*" || $val == null ? "*" : sprintf($format, $val);
            } else {
                $string .= $char;
            }
        }
        return $string;
    }
}
