<?php

namespace airmoi\FileMaker\Parser;

use airmoi\FileMaker\FileMaker;
use airmoi\FileMaker\Implementation as Impl;

class FileMaker_Parser_FMResultSet {

    private $_errorCode;
    private $_serverVersion;
    private $_parsedHead;
    private $_fieldList = array();
    private $_parsedFoundSet;
    private $_relatedSetNames = array();
    public $parsedResult = array();
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

    public function __construct(Impl\FileMaker_Implementation $fm) {
        $this->_fm = $fm;
    }

    public function parse($xml) {
        if (empty($xml)) {
            return new FileMaker_Error($this->_fm, 'Did not receive an XML document from the server.');
        }
        $this->_xmlParser = xml_parser_create('UTF-8');
        xml_set_object($this->_xmlParser, $this);
        xml_parser_set_option($this->_xmlParser, XML_OPTION_CASE_FOLDING, false);
        xml_parser_set_option($this->_xmlParser, XML_OPTION_TARGET_ENCODING, 'UTF-8');
        xml_set_element_handler($this->_xmlParser, '_start', '_end');
        xml_set_character_data_handler($this->_xmlParser, '_cdata');
        if (!@xml_parse($this->_xmlParser, $xml)) {
            return new FileMaker_Error($this->_fm, sprintf('XML error: %s at line %d', xml_error_string(xml_get_error_code($this->_xmlParser)), xml_get_current_line_number($this->_xmlParser)));
        }
        xml_parser_free($this->_xmlParser);
        if (!empty($this->_errorCode)) {
            return new FileMaker_Error($this->_fm, null, $this->_errorCode);
        }
        if (version_compare($this->_serverVersion['version'], FileMaker::getMinServerVersion(), '<')) {
            return new FileMaker_Error($this->_fm, 'This API requires at least version ' . FileMaker::getMinServerVersion() . ' of FileMaker Server to run (detected ' . $this->_serverVersion['version'] . ').');
        }
        $this->_isParsed = true;
        return true;
    }

    public function setResult($result, $recordClass = 'FileMaker_Record') {
        if (!$this->_isParsed) {
            return new FileMaker_Error($this->_fm, 'Attempt to get a result object before parsing data.');
        }
        if ($this->_result) {
            $result = $this->_result;
            return true;
        }
        $result->_impl->layout = new FileMaker_Layout($this->_fm);
        $this->setLayout($result->_impl->layout);
        $result->_impl->tableCount = $this->_parsedHead['total-count'];
        $result->_impl->foundSetCount = $this->_parsedFoundSet['count'];
        $result->_impl->fetchCount = $this->_parsedFoundSet['fetch-size'];
        $records = array();
        foreach ($this->parsedResult as $recordData) {
            $record = new $recordClass($result->_impl->layout);
            $record->_impl->fields = $recordData['fields'];
            $record->_impl->recordId = $recordData['record-id'];
            $record->_impl->modificationId = $recordData['mod-id'];
            if ($recordData['children']) {
                foreach ($recordData['children'] as $relatedSetName => $relatedRecords) {
                    $record->_impl->relatedSets[$relatedSetName] = array();
                    foreach ($relatedRecords as $relatedRecordData) {
                        $relatedRecord = new $recordClass($result->_impl->layout->getRelatedSet($relatedSetName));
                        $relatedRecord->_impl->fields = $relatedRecordData['fields'];
                        $relatedRecord->_impl->recordId = $relatedRecordData['record-id'];
                        $relatedRecord->_impl->modificationId = $relatedRecordData['mod-id'];
                        $relatedRecord->_impl->parent = $record;
                        $record->_impl->relatedSets[$relatedSetName][] = $relatedRecord;
                    }
                }
            }
            $records[] = $record;
        }
        $result->_impl->records = & $records;
        $this->_result = & $result;
        true;
    }

    public function setLayout(FileMaker\FileMaker_Layout $layout) {
        if (!$this->_isParsed) {
            return new FileMaker_Error($this->_fm, 'Attempt to get a layout object before parsing data.');
        }
        if ($this->_layout) {
            $layout = & $this->_layout;
            return true;
        }
        $layout->_impl->name = $this->_parsedHead['layout'];
        $layout->_impl->database = $this->_parsedHead['database'];
        foreach ($this->_fieldList as $fieldInfos) {
            $field = new FileMaker_Field($layout);
            $field->_impl->name = $fieldInfos['name'];
            $field->_impl->autoEntered = (bool) ($fieldInfos['auto-enter'] == 'yes');
            $field->_impl->global = (bool) ($fieldInfos['global'] == 'yes');
            $field->_impl->maxRepeat = (int) $fieldInfos['max-repeat'];
            $field->_impl->result = $fieldInfos['result'];
            $field->_impl->type = $fieldInfos['type'];
            if ($fieldInfos['not-empty'] == 'yes') {
                $field->_impl->validationRules[FILEMAKER_RULE_NOTEMPTY] = true;
                $field->_impl->validationMask |= FILEMAKER_RULE_NOTEMPTY;
            }
            if ($fieldInfos['numeric-only'] == 'yes') {
                $field->_impl->validationRules[FILEMAKER_RULE_NUMERICONLY] = true;
                $field->_impl->validationMask |= FILEMAKER_RULE_NUMERICONLY;
            }
            if (array_key_exists('max-characters', $fieldInfos)) {
                $field->_impl->maxCharacters = (int) $fieldInfos['max-characters'];
                $field->_impl->validationRules[FILEMAKER_RULE_MAXCHARACTERS] = true;
                $field->_impl->validationMask |= FILEMAKER_RULE_MAXCHARACTERS;
            }
            if ($fieldInfos['four-digit-year'] == 'yes') {
                $field->_impl->validationRules[FILEMAKER_RULE_FOURDIGITYEAR] = true;
                $field->_impl->validationMask |= FILEMAKER_RULE_FOURDIGITYEAR;
            }
            if ($fieldInfos['time-of-day'] == 'yes') {
                $field->_impl->validationRules[FILEMAKER_RULE_TIMEOFDAY] = true;
                $field->_impl->validationMask |= FILEMAKER_RULE_TIMEOFDAY;
            }
            if ($fieldInfos['four-digit-year'] == 'no' && $fieldInfos['result'] == 'timestamp') {
                $field->_impl->validationRules[FILEMAKER_RULE_TIMESTAMP_FIELD] = true;
                $field->_impl->validationMask |= FILEMAKER_RULE_TIMESTAMP_FIELD;
            }
            if ($fieldInfos['four-digit-year'] == 'no' && $fieldInfos['result'] == 'date') {
                $field->_impl->validationRules[FILEMAKER_RULE_DATE_FIELD] = true;
                $field->_impl->validationMask |= FILEMAKER_RULE_DATE_FIELD;
            }
            if ($fieldInfos['time-of-day'] == 'no' && $fieldInfos['result'] == 'time') {
                $field->_impl->validationRules[FILEMAKER_RULE_TIME_FIELD] = true;
                $field->_impl->validationMask |= FILEMAKER_RULE_TIME_FIELD;
            }
            $layout->_impl->fields[$field->getName()] = $field;
        }
        foreach ($this->_relatedSetNames as $relatedSetName => $fields) {
            $relatedSet = new FileMaker_RelatedSet($layout);
            $relatedSet->_impl->name = $relatedSetName;
            foreach ($fields as $fieldInfos) {
                $field = new FileMaker_Field($relatedSet);
                $field->_impl->name = $fieldInfos['name'];
                $field->_impl->autoEntered = (bool) ($fieldInfos['auto-enter'] == 'yes');
                $field->_impl->global = (bool) ($fieldInfos['global'] == 'yes');
                $field->_impl->maxRepeat = (int) $fieldInfos['max-repeat'];
                $field->_impl->result = $fieldInfos['result'];
                $field->_impl->type = $fieldInfos['type'];
                if ($fieldInfos['not-empty'] == 'yes') {
                    $field->_impl->validationRules[FILEMAKER_RULE_NOTEMPTY] = true;
                    $field->_impl->validationMask |= FILEMAKER_RULE_NOTEMPTY;
                }
                if ($fieldInfos['numeric-only'] == 'yes') {
                    $field->_impl->validationRules[FILEMAKER_RULE_NUMERICONLY] = true;
                    $field->_impl->validationMask |= FILEMAKER_RULE_NUMERICONLY;
                }
                if (array_key_exists('max-characters', $fieldInfos)) {
                    $field->_impl->maxCharacters = (int) $fieldInfos['max-characters'];
                    $field->_impl->validationRules[FILEMAKER_RULE_MAXCHARACTERS] = true;
                    $field->_impl->validationMask |= FILEMAKER_RULE_MAXCHARACTERS;
                }
                if ($fieldInfos['four-digit-year'] == 'yes') {
                    $field->_impl->validationRules[FILEMAKER_RULE_FOURDIGITYEAR] = true;
                    $field->_impl->validationMask |= FILEMAKER_RULE_FOURDIGITYEAR;
                }
                if ($fieldInfos['time-of-day'] == 'yes' || $fieldInfos['result'] == 'time') {
                    $field->_impl->validationRules[FILEMAKER_RULE_TIMEOFDAY] = true;
                    $field->_impl->validationMask |= FILEMAKER_RULE_TIMEOFDAY;
                }
                if ($fieldInfos['four-digit-year'] == 'no' && $fieldInfos['result'] == 'timestamp') {
                    $field->_impl->validationRules[FILEMAKER_RULE_TIMESTAMP_FIELD] = true;
                    $field->_impl->validationMask |= FILEMAKER_RULE_TIMESTAMP_FIELD;
                }
                if ($fieldInfos['four-digit-year'] == 'no' && $fieldInfos['result'] == 'date') {
                    $field->_impl->validationRules[FILEMAKER_RULE_DATE_FIELD] = true;
                    $field->_impl->validationMask |= FILEMAKER_RULE_DATE_FIELD;
                }
                if ($fieldInfos['time-of-day'] == 'no' && $fieldInfos['result'] == 'time') {
                    $field->_impl->validationRules[FILEMAKER_RULE_TIME_FIELD] = true;
                    $field->_impl->validationMask |= FILEMAKER_RULE_TIME_FIELD;
                }
                $relatedSet->_impl->fields[$field->getName()] = $field;
            }
            $layout->_impl->relatedSets[$relatedSet->getName()] = $relatedSet;
        }
        $this->_layout = & $layout;
        return true;
    }
    /**
     * 
     * @param type $unusedVar
     * @param type $tag
     * @param type $datas
     */
    private function _start($unusedVar, $tag, $datas) {
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
                $this->_relatedSetNames[$datas['table']] = array();
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
     * 
     * @param type $unusedVar
     * @param type $tag
     */
    private function _end($unusedVar, $tag) {
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
     * 
     * @param type $unusedVar
     * @param type $data
     */
    private function _cdata($unusedVar, $data) {
        $this->_cdata.= $this->_fm->toOutputCharset($data);
    }

}
