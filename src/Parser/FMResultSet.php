<?php
/**
 * @copyright Copyright (c) 2016 by 1-more-thing (http://1-more-thing.com) All rights reserved.
 * @license BSD
 */
namespace airmoi\FileMaker\Parser;

use airmoi\FileMaker\FileMaker;
use airmoi\FileMaker\FileMakerException;
use airmoi\FileMaker\Object\Layout;
use airmoi\FileMaker\Object\Field;
use airmoi\FileMaker\Object\RelatedSet;
use airmoi\FileMaker\Object\Result;

/**
 * Class used to parse FMResultSet structure
 * @package FileMAker
 */
class FMResultSet
{
    /**
     * @var FileMaker
     */
    private $fm;

    /**
     * Array that stores parsed records
     * @var \airmoi\FileMaker\Object\Record[]
     */
    public $parsedResult = [];

    private $errorCode;
    private $serverVersion;
    private $parsedHead;
    private $fieldList = [];
    private $parsedFoundSet;
    private $relatedSetNames = [];
    private $currentRelatedSet;
    private $currentRecord;
    private $parentRecord;
    private $currentField;
    private $cdata;
    private $xmlParser;
    private $isParsed = false;
    private $result;
    private $layout;

    /**
     * FMResultSet constructor.
     * @param FileMaker $fm
     */
    public function __construct(FileMaker $fm)
    {
        $this->fm = $fm;
    }

    /**
     * Parse the provided xml
     *
     * @param string $xml
     * @return FileMakerException|boolean
     * @throws FileMakerException
     */
    public function parse($xml)
    {
        if (empty($xml)) {
            return $this->fm->returnOrThrowException('Did not receive an XML document from the server.');
        }
        $this->xmlParser = xml_parser_create('UTF-8');
        xml_set_object($this->xmlParser, $this);
        xml_parser_set_option($this->xmlParser, XML_OPTION_CASE_FOLDING, false);
        xml_parser_set_option($this->xmlParser, XML_OPTION_TARGET_ENCODING, 'UTF-8');
        /** @psalm-suppress UndefinedFunction */
        xml_set_element_handler($this->xmlParser, 'start', 'end');
        /** @psalm-suppress UndefinedFunction */
        xml_set_character_data_handler($this->xmlParser, 'cdata');
        if (!@xml_parse($this->xmlParser, $xml)) {
            return $this->fm->returnOrThrowException(
                sprintf(
                    'XML error: %s at line %d',
                    xml_error_string(xml_get_error_code($this->xmlParser)),
                    xml_get_current_line_number($this->xmlParser)
                )
            );
        }
        xml_parser_free($this->xmlParser);
        unset($this->xmlParser);
        if (!empty($this->errorCode)) {
            return $this->fm->returnOrThrowException(null, $this->errorCode);
        }
        if (version_compare($this->serverVersion['version'], FileMaker::getMinServerVersion(), '<')) {
            return $this->fm->returnOrThrowException(
                'This API requires at least version ' . FileMaker::getMinServerVersion()
                . ' of FileMaker Server to run (detected ' . $this->serverVersion['version'] . ').'
            );
        }
        $this->isParsed = true;
        return true;
    }

    /**
     * Populate a result object with parsed datas
     *
     * @param \airmoi\FileMaker\Object\Result $result
     * @param string $recordClass string representing the record class name to use
     * @return FileMakerException|boolean
     * @throws FileMakerException
     */
    public function setResult(Result $result, $recordClass = 'airmoi\FileMaker\Object\Record')
    {
        if (!$this->isParsed) {
            return $this->fm->returnOrThrowException('Attempt to get a result object before parsing data.');
        }
        if ($this->result) {
            return true;
        }
        $result->layout = new Layout($this->fm);
        $this->setLayout($result->layout);
        $result->tableCount = $this->parsedHead['total-count'];
        $result->foundSetCount = $this->parsedFoundSet['count'];
        $result->fetchCount = $this->parsedFoundSet['fetch-size'];
        $records = [];
        foreach ($this->parsedResult as $recordData) {
            $record = new $recordClass($result->layout);
            $record->fields = $recordData['fields'];
            $record->recordId = $recordData['record-id'];
            $record->modificationId = $recordData['mod-id'];
            if ($recordData['children']) {
                foreach ($recordData['children'] as $relatedSetName => $relatedRecords) {
                    $record->relatedSets[$relatedSetName] = [];
                    foreach ($relatedRecords as $relatedRecordData) {
                        $relatedRecord = new $recordClass($result->layout->getRelatedSet($relatedSetName));
                        $relatedRecord->fields = $relatedRecordData['fields'];
                        $relatedRecord->recordId = $relatedRecordData['record-id'];
                        $relatedRecord->modificationId = $relatedRecordData['mod-id'];
                        $relatedRecord->parent = $record;
                        $relatedRecord->relatedSetName = $relatedSetName;
                        $record->relatedSets[$relatedSetName][] = $relatedRecord;
                    }
                }
            }
            $records[] = $record;
        }
        $result->records = & $records;
        $this->result = & $result;
        return true;
    }

    /**
     * Populate a layout object with parsed datas
     *
     * @param Layout $layout
     * @return FileMakerException|boolean
     * @throws FileMakerException
     */
    public function setLayout(Layout $layout)
    {
        if (!$this->isParsed) {
            return $this->fm->returnOrThrowException('Attempt to get a layout object before parsing data.');
        }
        if ($this->layout === $layout) {
            return true;
        }
        $layout->name = $this->parsedHead['layout'];
        $layout->database = $this->parsedHead['database'];
        $layout->table = $this->parsedHead['table'];
        foreach ($this->fieldList as $fieldInfos) {
            $field = new Field($layout);
            $field->name = $fieldInfos['name'];
            $field->autoEntered = (bool) ($fieldInfos['auto-enter'] === 'yes');
            $field->global = (bool) ($fieldInfos['global'] === 'yes');
            $field->maxRepeat = (int) $fieldInfos['max-repeat'];
            $field->result = $fieldInfos['result'];
            $field->type = $fieldInfos['type'];
            if ($fieldInfos['not-empty'] === 'yes') {
                $field->validationRules[FileMaker::RULE_NOTEMPTY] = true;
                $field->validationMask |= FileMaker::RULE_NOTEMPTY;
            }
            if ($fieldInfos['numeric-only'] === 'yes') {
                $field->validationRules[FileMaker::RULE_NUMERICONLY] = true;
                $field->validationMask |= FileMaker::RULE_NUMERICONLY;
            }
            if (array_key_exists('max-characters', $fieldInfos)) {
                $field->maxCharacters = (int) $fieldInfos['max-characters'];
                $field->validationRules[FileMaker::RULE_MAXCHARACTERS] = true;
                $field->validationMask |= FileMaker::RULE_MAXCHARACTERS;
            }
            if ($fieldInfos['four-digit-year'] === 'yes') {
                $field->validationRules[FileMaker::RULE_FOURDIGITYEAR] = true;
                $field->validationMask |= FileMaker::RULE_FOURDIGITYEAR;
            }
            if ($fieldInfos['time-of-day'] === 'yes') {
                $field->validationRules[FileMaker::RULE_TIMEOFDAY] = true;
                $field->validationMask |= FileMaker::RULE_TIMEOFDAY;
            }
            if ($fieldInfos['four-digit-year'] === 'no' && $fieldInfos['result'] === 'timestamp') {
                $field->validationRules[FileMaker::RULE_TIMESTAMP_FIELD] = true;
                $field->validationMask |= FileMaker::RULE_TIMESTAMP_FIELD;
            }
            if ($fieldInfos['four-digit-year'] === 'no' && $fieldInfos['result'] === 'date') {
                $field->validationRules[FileMaker::RULE_DATE_FIELD] = true;
                $field->validationMask |= FileMaker::RULE_DATE_FIELD;
            }
            if ($fieldInfos['time-of-day'] === 'no' && $fieldInfos['result'] === 'time') {
                $field->validationRules[FileMaker::RULE_TIME_FIELD] = true;
                $field->validationMask |= FileMaker::RULE_TIME_FIELD;
            }
            $layout->fields[$field->getName()] = $field;
        }
        foreach ($this->relatedSetNames as $relatedSetName => $fields) {
            $relatedSet = new RelatedSet($layout);
            $relatedSet->name = $relatedSetName;
            foreach ($fields as $fieldInfos) {
                $field = new Field($layout);
                $field->name = $fieldInfos['name'];
                $field->autoEntered = (bool) ($fieldInfos['auto-enter'] === 'yes');
                $field->global = (bool) ($fieldInfos['global'] === 'yes');
                $field->maxRepeat = (int) $fieldInfos['max-repeat'];
                $field->result = $fieldInfos['result'];
                $field->type = $fieldInfos['type'];
                if ($fieldInfos['not-empty'] === 'yes') {
                    $field->validationRules[FileMaker::RULE_NOTEMPTY] = true;
                    $field->validationMask |= FileMaker::RULE_NOTEMPTY;
                }
                if ($fieldInfos['numeric-only'] === 'yes') {
                    $field->validationRules[FileMaker::RULE_NUMERICONLY] = true;
                    $field->validationMask |= FileMaker::RULE_NUMERICONLY;
                }
                if (array_key_exists('max-characters', $fieldInfos)) {
                    $field->maxCharacters = (int) $fieldInfos['max-characters'];
                    $field->validationRules[FileMaker::RULE_MAXCHARACTERS] = true;
                    $field->validationMask |= FileMaker::RULE_MAXCHARACTERS;
                }
                if ($fieldInfos['four-digit-year'] === 'yes') {
                    $field->validationRules[FileMaker::RULE_FOURDIGITYEAR] = true;
                    $field->validationMask |= FileMaker::RULE_FOURDIGITYEAR;
                }
                if ($fieldInfos['time-of-day'] === 'yes' || $fieldInfos['result'] === 'time') {
                    $field->validationRules[FileMaker::RULE_TIMEOFDAY] = true;
                    $field->validationMask |= FileMaker::RULE_TIMEOFDAY;
                }
                if ($fieldInfos['four-digit-year'] === 'no' && $fieldInfos['result'] === 'timestamp') {
                    $field->validationRules[FileMaker::RULE_TIMESTAMP_FIELD] = true;
                    $field->validationMask |= FileMaker::RULE_TIMESTAMP_FIELD;
                }
                if ($fieldInfos['four-digit-year'] === 'no' && $fieldInfos['result'] === 'date') {
                    $field->validationRules[FileMaker::RULE_DATE_FIELD] = true;
                    $field->validationMask |= FileMaker::RULE_DATE_FIELD;
                }
                if ($fieldInfos['time-of-day'] === 'no' && $fieldInfos['result'] === 'time') {
                    $field->validationRules[FileMaker::RULE_TIME_FIELD] = true;
                    $field->validationMask |= FileMaker::RULE_TIME_FIELD;
                }
                $relatedSet->fields[$field->getName()] = $field;
            }
            $layout->relatedSets[$relatedSet->getName()] = $relatedSet;
        }
        $this->layout = $layout;
        return true;
    }
    /**
     * xml_parser start element handler
     *
     * @param resource $parser
     * @param string $tag
     * @param array $datas
     * @return void
     */
    private function start($parser, $tag, $datas)
    {
        $datas = $this->fm->toOutputCharset($datas);
        switch ($tag) {
            case 'error':
                $this->errorCode = $datas['code'];
                break;
            case 'product':
                $this->serverVersion = $datas;
                break;
            case 'datasource':
                $this->parsedHead = $datas;
                break;
            case 'relatedset-definition':
                $this->relatedSetNames[$datas['table']] = [];
                $this->currentRelatedSet = $datas['table'];
                break;
            case 'field-definition':
                if ($this->currentRelatedSet) {
                    $this->relatedSetNames[$this->currentRelatedSet][] = $datas;
                } else {
                    $this->fieldList[] = $datas;
                }
                break;
            case 'resultset':
                $this->parsedFoundSet = $datas;
                break;
            case 'relatedset':
                $this->currentRelatedSet = $datas['table'];
                $this->parentRecord = $this->currentRecord;
                $this->parentRecord['children'][$this->currentRelatedSet] = [];
                $this->currentRecord = null;
                break;
            case 'record':
                $this->currentRecord = [
                    'record-id' => $datas['record-id'],
                    'mod-id'    => $datas['mod-id'],
                    'fields'    => [],
                    'children'  => [],
                ];
                break;
            case 'field':
                $this->currentField = $datas['name'];
                $this->currentRecord['fields'][$this->currentField] = [];
                break;
            case 'data':
                $this->cdata = '';
                break;
        }
    }

    /**
     * xml_parser end element handler
     *
     * @param mixed $parser
     * @param string $tag
     * @return void
     */
    private function end($parser, $tag)
    {
        switch ($tag) {
            case 'relatedset-definition':
                $this->currentRelatedSet = null;
                break;
            case 'relatedset':
                $this->currentRelatedSet = null;
                $this->currentRecord = $this->parentRecord;
                $this->parentRecord = null;
                break;
            case 'record':
                if ($this->currentRelatedSet) {
                    $this->parentRecord['children'][$this->currentRelatedSet][] = $this->currentRecord;
                } else {
                    $this->parsedResult[] = $this->currentRecord;
                }
                $this->currentRecord = null;
                break;
            case 'field':
                $this->currentField = null;
                break;
            case 'data':
                $this->currentRecord['fields'][$this->currentField][] = $this->cdata;
                $this->cdata = null;
                break;
        }
    }

    /**
     * xml_parser character data handler (cdata)
     *
     * @param resource $parser
     * @param string $data
     * @return void
     */
    private function cdata($parser, $data)
    {
        $this->cdata .= $this->fm->toOutputCharset($data);
    }
}
