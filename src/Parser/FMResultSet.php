<?php
/**
 * @copyright Copyright (c) 2016 by 1-more-thing (http://1-more-thing.com) All rights reserved.
 * @licence BSD
 */
namespace airmoi\FileMaker\Parser;

use airmoi\FileMaker\FileMaker;
use airmoi\FileMaker\FileMakerException;
use airmoi\FileMaker\Object\Layout;
use airmoi\FileMaker\Object\Field;
use airmoi\FileMaker\Object\RelatedSet;

/**
 * Class used to parse FMResultSet structure
 * @package FileMAker
 */
class FMResultSet {
    
    /**
     * Array that stores parsed records
     * @var \airmoi\FileMaker\Object\Record[] 
     */
    public $parsedResult = array();
    
    private $_errorCode;
    private $_serverVersion;
    private $_parsedHead;
    private $_fieldList = array();
    private $_parsedFoundSet;
    private $_relatedSetNames = array();
    private $_currentRelatedSet;
    private $_currentRecord;
    private $_parentRecord;
    private $_currentField;
    private $_cdata;
    private $_fm;
    private $_xmlParser;
    private $_isParsed = false;
    private $_result;
    private $_layout;

    public function __construct(FileMaker $fm) {
        $this->_fm = $fm;
    }

    /**
     * Parse the provided xml
     * 
     * @param string $xml
     * @return FileMakerException|boolean
     * @throws FileMakerException
     */
    public function parse($xml) {
        if (empty($xml)) {
            $error = new FileMakerException($this->_fm, 'Did not receive an XML document from the server.');
            if($this->_fm->getProperty('errorHandling') == 'default') {
                return $error;
            }
            throw $error;
        }
        $this->_xmlParser = xml_parser_create('UTF-8');
        xml_set_object($this->_xmlParser, $this);
        xml_parser_set_option($this->_xmlParser, XML_OPTION_CASE_FOLDING, false);
        xml_parser_set_option($this->_xmlParser, XML_OPTION_TARGET_ENCODING, 'UTF-8');
        xml_set_element_handler($this->_xmlParser, '_start', '_end');
        xml_set_character_data_handler($this->_xmlParser, '_cdata');
        if (!@xml_parse($this->_xmlParser, $xml)) {
            $error = new FileMakerException($this->_fm, sprintf('XML error: %s at line %d', xml_error_string(xml_get_error_code($this->_xmlParser)), xml_get_current_line_number($this->_xmlParser)));
            if($this->_fm->getProperty('errorHandling') == 'default') {
                return $error;
            }
            throw $error;
        }
        xml_parser_free($this->_xmlParser);
        if (!empty($this->_errorCode)) {
            $error = new FileMakerException($this->_fm, null, $this->_errorCode);
            if($this->_fm->getProperty('errorHandling') == 'default') {
                return $error;
            }
            throw $error;
        }
        if (version_compare($this->_serverVersion['version'], FileMaker::getMinServerVersion(), '<')) {
            $error = new FileMakerException($this->_fm, 'This API requires at least version ' . FileMaker::getMinServerVersion() . ' of FileMaker Server to run (detected ' . $this->_serverVersion['version'] . ').');
            if($this->_fm->getProperty('errorHandling') == 'default') {
                return $error;
            }
            throw $error;
        }
        $this->_isParsed = true;
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
    public function setResult(\airmoi\FileMaker\Object\Result $result, $recordClass = 'airmoi\FileMaker\Object\Record') {
        if (!$this->_isParsed) {
            $error = new FileMakerException($this->_fm, 'Attempt to get a result object before parsing data.');
            if($this->_fm->getProperty('errorHandling') == 'default') {
                return $error;
            }
            throw $error;
        }
        if ($this->_result) {
            $result = $this->_result;
            return true;
        }
        $result->layout = new Layout($this->_fm);
        $this->setLayout($result->layout);
        $result->tableCount = $this->_parsedHead['total-count'];
        $result->foundSetCount = $this->_parsedFoundSet['count'];
        $result->fetchCount = $this->_parsedFoundSet['fetch-size'];
        $records = array();
        foreach ($this->parsedResult as $recordData) {
            $record = new $recordClass($result->layout);
            $record->fields = $recordData['fields'];
            $record->recordId = $recordData['record-id'];
            $record->modificationId = $recordData['mod-id'];
            if ($recordData['children']) {
                foreach ($recordData['children'] as $relatedSetName => $relatedRecords) {
                    $record->relatedSets[$relatedSetName] = array();
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
        $this->_result = & $result;
        true;
    }

    /**
     * Populate a layout object with parsed datas
     * 
     * @param Layout $layout
     * @return FileMakerException|boolean
     * @throws FileMakerException
     */
    public function setLayout(Layout $layout) {
        if (!$this->_isParsed) {
            $error = new FileMakerException($this->_fm, 'Attempt to get a layout object before parsing data.');
            if($this->_fm->getProperty('errorHandling') == 'default') {
                return $error;
            }
            throw $error;
        }
        if ($this->_layout === $layout) {
            return true;
        }
        $layout->name = $this->_parsedHead['layout'];
        $layout->database = $this->_parsedHead['database'];
        $layout->table = $this->_parsedHead['table'];
        foreach ($this->_fieldList as $fieldInfos) {
            $field = new Field($layout);
            $field->name = $fieldInfos['name'];
            $field->autoEntered = (bool) ($fieldInfos['auto-enter'] == 'yes');
            $field->global = (bool) ($fieldInfos['global'] == 'yes');
            $field->maxRepeat = (int) $fieldInfos['max-repeat'];
            $field->result = $fieldInfos['result'];
            $field->type = $fieldInfos['type'];
            if ($fieldInfos['not-empty'] == 'yes') {
                $field->validationRules[FileMaker::RULE_NOTEMPTY] = true;
                $field->validationMask |= FileMaker::RULE_NOTEMPTY;
            }
            if ($fieldInfos['numeric-only'] == 'yes') {
                $field->validationRules[FileMaker::RULE_NUMERICONLY] = true;
                $field->validationMask |= FileMaker::RULE_NUMERICONLY;
            }
            if (array_key_exists('max-characters', $fieldInfos)) {
                $field->maxCharacters = (int) $fieldInfos['max-characters'];
                $field->validationRules[FileMaker::RULE_MAXCHARACTERS] = true;
                $field->validationMask |= FileMaker::RULE_MAXCHARACTERS;
            }
            if ($fieldInfos['four-digit-year'] == 'yes') {
                $field->validationRules[FileMaker::RULE_FOURDIGITYEAR] = true;
                $field->validationMask |= FileMaker::RULE_FOURDIGITYEAR;
            }
            if ($fieldInfos['time-of-day'] == 'yes') {
                $field->validationRules[FileMaker::RULE_TIMEOFDAY] = true;
                $field->validationMask |= FileMaker::RULE_TIMEOFDAY;
            }
            if ($fieldInfos['four-digit-year'] == 'no' && $fieldInfos['result'] == 'timestamp') {
                $field->validationRules[FileMaker::RULE_TIMESTAMP_FIELD] = true;
                $field->validationMask |= FileMaker::RULE_TIMESTAMP_FIELD;
            }
            if ($fieldInfos['four-digit-year'] == 'no' && $fieldInfos['result'] == 'date') {
                $field->validationRules[FileMaker::RULE_DATE_FIELD] = true;
                $field->validationMask |= FileMaker::RULE_DATE_FIELD;
            }
            if ($fieldInfos['time-of-day'] == 'no' && $fieldInfos['result'] == 'time') {
                $field->validationRules[FileMaker::RULE_TIME_FIELD] = true;
                $field->validationMask |= FileMaker::RULE_TIME_FIELD;
            }
            $layout->fields[$field->getName()] = $field;
        }
        foreach ($this->_relatedSetNames as $relatedSetName => $fields) {
            $relatedSet = new RelatedSet($layout);
            $relatedSet->name = $relatedSetName;
            foreach ($fields as $fieldInfos) {
                $field = new Field($layout);
                $field->name = $fieldInfos['name'];
                $field->autoEntered = (bool) ($fieldInfos['auto-enter'] == 'yes');
                $field->global = (bool) ($fieldInfos['global'] == 'yes');
                $field->maxRepeat = (int) $fieldInfos['max-repeat'];
                $field->result = $fieldInfos['result'];
                $field->type = $fieldInfos['type'];
                if ($fieldInfos['not-empty'] == 'yes') {
                    $field->validationRules[FileMaker::RULE_NOTEMPTY] = true;
                    $field->validationMask |= FileMaker::RULE_NOTEMPTY;
                }
                if ($fieldInfos['numeric-only'] == 'yes') {
                    $field->validationRules[FileMaker::RULE_NUMERICONLY] = true;
                    $field->validationMask |= FileMaker::RULE_NUMERICONLY;
                }
                if (array_key_exists('max-characters', $fieldInfos)) {
                    $field->maxCharacters = (int) $fieldInfos['max-characters'];
                    $field->validationRules[FileMaker::RULE_MAXCHARACTERS] = true;
                    $field->validationMask |= FileMaker::RULE_MAXCHARACTERS;
                }
                if ($fieldInfos['four-digit-year'] == 'yes') {
                    $field->validationRules[FileMaker::RULE_FOURDIGITYEAR] = true;
                    $field->validationMask |= FileMaker::RULE_FOURDIGITYEAR;
                }
                if ($fieldInfos['time-of-day'] == 'yes' || $fieldInfos['result'] == 'time') {
                    $field->validationRules[FileMaker::RULE_TIMEOFDAY] = true;
                    $field->validationMask |= FileMaker::RULE_TIMEOFDAY;
                }
                if ($fieldInfos['four-digit-year'] == 'no' && $fieldInfos['result'] == 'timestamp') {
                    $field->validationRules[FileMaker::RULE_TIMESTAMP_FIELD] = true;
                    $field->validationMask |= FileMaker::RULE_TIMESTAMP_FIELD;
                }
                if ($fieldInfos['four-digit-year'] == 'no' && $fieldInfos['result'] == 'date') {
                    $field->validationRules[FileMaker::RULE_DATE_FIELD] = true;
                    $field->validationMask |= FileMaker::RULE_DATE_FIELD;
                }
                if ($fieldInfos['time-of-day'] == 'no' && $fieldInfos['result'] == 'time') {
                    $field->validationRules[FileMaker::RULE_TIME_FIELD] = true;
                    $field->validationMask |= FileMaker::RULE_TIME_FIELD;
                }
                $relatedSet->fields[$field->getName()] = $field;
            }
            $layout->relatedSets[$relatedSet->getName()] = $relatedSet;
        }
        $this->_layout = $layout;
        return true;
    }
    /**
     * xml_parser start element handler
     * 
     * @param resource $parser
     * @param string $tag
     * @param array $datas
     */
    private function _start($parser, $tag, $datas) {
        $datas = $this->_fm->toOutputCharset($datas);
        switch ($tag) {
            case 'error':
                $this->_errorCode = $datas['code'];
                break;
            case 'product':
                $this->_serverVersion = $datas;
                break;
            case 'datasource':
                $this->_parsedHead = $datas;
                break;
            case 'relatedset-definition':
                $this->_relatedSetNames[$datas['table']] = [];
                $this->_currentRelatedSet = $datas['table'];
                break;
            case 'field-definition':
                if ($this->_currentRelatedSet) {
                    $this->_relatedSetNames[$this->_currentRelatedSet][] = $datas;
                } else {
                    $this->_fieldList[] = $datas;
                }
                break;
            case 'resultset':
                $this->_parsedFoundSet = $datas;
                break;
            case 'relatedset':
                $this->_currentRelatedSet = $datas['table'];
                $this->_parentRecord = $this->_currentRecord;
                $this->_parentRecord['children'][$this->_currentRelatedSet] = array();
                $this->_currentRecord = null;
                break;
            case 'record':
                $this->_currentRecord = array('record-id' => $datas['record-id'],
                            'mod-id' => $datas['mod-id'],
                            'fields' => array(),
                            'children' => array());
                break;
            case 'field':
                $this->_currentField = $datas['name'];
                $this->_currentRecord['fields'][$this->_currentField] = array();
                break;
            case 'data':
                $this->_cdata = '';
                break;
        }
    }

    /**
     * xml_parser end element handler
     * 
     * @param mixed $parser
     * @param string $tag
     */
    private function _end($parser, $tag) {
        switch ($tag) {
            case 'relatedset-definition':
                $this->_currentRelatedSet = null;
                break;
            case 'relatedset':
                $this->_currentRelatedSet = null;
                $this->_currentRecord = $this->_parentRecord;
                $this->_parentRecord = null;
                break;
            case 'record':
                if ($this->_currentRelatedSet) {
                    $this->_parentRecord['children'][$this->_currentRelatedSet][] = $this->_currentRecord;
                } else {
                    $this->parsedResult[] = $this->_currentRecord;
                }
                $this->_currentRecord = null;
                break;
            case 'field':
                $this->_currentField = null;
                break;
            case 'data':
                $this->_currentRecord['fields'][$this->_currentField][] = $this->_cdata;
                $this->_cdata = null;
                break;
        }
    }

    /**
     * xml_parser character data handler (cdata)
     * 
     * @param resource $parser
     * @param string $data
     */
    private function _cdata($parser, $data) {
        $this->_cdata.= $this->_fm->toOutputCharset($data);
    }

}
