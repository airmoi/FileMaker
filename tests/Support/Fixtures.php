<?php

namespace airmoi\FileMaker\Tests\Support;

/**
 * Builders for FileMaker server responses
 */
class Fixtures
{
    /**
     * Default field definitions used by the "sample" layout
     */
    public static function sampleFields(): array
    {
        return [
            'id' => ['result' => 'number', 'auto-enter' => 'yes', 'not-empty' => 'yes'],
            'name' => ['result' => 'text', 'not-empty' => 'yes'],
            'code' => ['result' => 'text', 'not-empty' => 'yes'],
            'note' => ['result' => 'text'],
            'date_field' => ['result' => 'date'],
            'timestamp_field' => ['result' => 'timestamp'],
        ];
    }

    /**
     * Build an fmresultset XML document
     *
     * @param array $fields field name => attributes overrides
     * @param array $records list of [recordId => [fieldName => value]]
     */
    public static function fmResultSet(
        array $fields = [],
        array $records = [],
        int $errorCode = 0,
        string $layout = 'sample',
        string $table = 'sample'
    ): string {
        $fields = $fields ?: self::sampleFields();
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>' . "\n"
            . '<fmresultset xmlns="http://www.filemaker.com/xml/fmresultset" version="1.0">'
            . '<error code="' . $errorCode . '"/>'
            . '<product build="01/01/2023" name="FileMaker Web Publishing Engine" version="20.3.2.200"/>'
            . '<datasource database="test" date-format="MM/dd/yyyy" layout="' . $layout . '" table="' . $table . '"'
            . ' time-format="HH:mm:ss" timestamp-format="MM/dd/yyyy HH:mm:ss" total-count="' . count($records) . '"/>'
            . '<metadata>';
        foreach ($fields as $name => $attributes) {
            $attributes += [
                'auto-enter' => 'no',
                'four-digit-year' => 'no',
                'global' => 'no',
                'max-repeat' => '1',
                'name' => $name,
                'not-empty' => 'no',
                'numeric-only' => 'no',
                'result' => 'text',
                'time-of-day' => 'no',
                'type' => 'normal',
            ];
            $xml .= '<field-definition';
            foreach ($attributes as $key => $value) {
                $xml .= ' ' . $key . '="' . htmlspecialchars((string) $value) . '"';
            }
            $xml .= '/>';
        }
        $xml .= '</metadata>'
            . '<resultset count="' . count($records) . '" fetch-size="' . count($records) . '">';
        foreach ($records as $recordId => $values) {
            $xml .= '<record mod-id="1" record-id="' . $recordId . '">';
            foreach ($values as $name => $value) {
                $xml .= '<field name="' . htmlspecialchars($name) . '"><data>'
                    . htmlspecialchars((string) $value) . '</data></field>';
            }
            $xml .= '</record>';
        }
        return $xml . '</resultset></fmresultset>';
    }

    public static function dataApi(array $response = [], int $code = 0, string $message = 'OK'): string
    {
        return json_encode([
            'response' => (object) $response,
            'messages' => [['code' => (string) $code, 'message' => $message]],
        ]);
    }
}
