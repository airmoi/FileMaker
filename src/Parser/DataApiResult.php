<?php


namespace airmoi\FileMaker\Parser;


use airmoi\FileMaker\FileMaker;
use airmoi\FileMaker\FileMakerException;
use airmoi\FileMaker\Object\Field;
use airmoi\FileMaker\Object\Layout;
use airmoi\FileMaker\Object\RelatedSet;
use airmoi\FileMaker\Object\Result;

class DataApiResult
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
    private $isParsed = false;
    private $result = null;
    private $layout = null;

    /**
     * FMResultSet constructor.
     * @param FileMaker $fm
     */
    public function __construct(FileMaker $fm)
    {
        $this->fm = $fm;
    }

    public static function parseError($response)
    {
        $json = json_decode($response, true);
        if (!$json) {
            return [
                "message" => json_last_error_msg(),
                "code" => json_last_error()
            ];
        }

        return [
            "message" => $json['messages'][0]['message'],
            "code" => $json['messages'][0]['code']
        ];
    }
    /**
     * Parse the provided JSON
     *
     * @param string $response
     * @return FileMakerException|boolean
     * @throws FileMakerException
     */
    public function parse($response)
    {
        $this->parsedResult = json_decode($response, true);
        $messages = self::parseError($response);

        if ($messages['code'] != 0) {
            return $this->fm->returnOrThrowException(
                $this->parsedResult['messages'][0]['message'],
                $this->parsedResult['messages'][0]['code']
            );
        }
        $this->parsedResult = $this->parsedResult['response'];
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

        $result->layout = $this->fm->getLayout($this->parsedResult['dataInfo']['layout'], null, false);
        $this->setLayout($result->layout);

        //Update layoutCache here has record result contains extra usefull metas
        $this->fm->cacheSet('layout-' . $result->layout->getName(), $result->layout);

        $result->tableCount = $this->parsedResult['dataInfo']['totalRecordCount'];
        $result->foundSetCount = $this->parsedResult['dataInfo']['foundCount'];
        $result->fetchCount = $this->parsedResult['dataInfo']['returnedCount'];
        $records = [];
        foreach ($this->parsedResult['data'] as $recordData) {
            $record = new $recordClass($result->layout);
            $record->fields = $this->parseFields($recordData['fieldData']);
            $record->recordId = $recordData['recordId'];
            $record->modificationId = $recordData['modId'];
            if ($recordData['portalData']) {
                foreach ($recordData['portalData'] as $relatedSetName => $relatedRecords) {
                    $record->relatedSets[$relatedSetName] = [];
                    foreach ($relatedRecords as $relatedRecordData) {
                        $relatedRecord = new $recordClass($result->layout->getRelatedSet($relatedSetName));
                        $relatedRecord->recordId = $relatedRecordData['recordId'];
                        $relatedRecord->modificationId = $relatedRecordData['modId'];
                        unset($relatedRecordData['recordId']);
                        unset($relatedRecordData['modId']);
                        $relatedRecord->fields = $this->parseFields($relatedRecordData);
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

        //handle foundset meta
        if (isset($this->parsedResult['dataInfo'])) {
            $layout->name = $this->parsedResult['dataInfo']['layout'];
            $layout->database = $this->parsedResult['dataInfo']['database'];
            $layout->table = $this->parsedResult['dataInfo']['table'];

            //Get First record to retrieve portal meta (seriously dumb)
            if (isset($this->parsedResult['data'][0]['portalDataInfo'])) {
                foreach ($this->parsedResult['data'][0]['portalDataInfo'] as $portal) {
                    $relatedSet = new RelatedSet($layout);
                    $relatedSet->name = isset($portal['portalObjectName']) ? $portal['portalObjectName'] : $portal['table'];
                    $relatedSet->database = $portal['database'];
                    $relatedSet->table = $portal['table'];
                }
            }
            $this->layout = $layout;
            return true;
        } else {
            foreach ($this->parsedResult['fieldMetaData'] as $fieldInfos) {
                $field = new Field($layout);
                $field->name = $fieldInfos['name'];
                $field->autoEntered = (bool) ($fieldInfos['autoEnter'] === 'yes');
                $field->global = (bool) ($fieldInfos['global'] === 'yes');
                $field->maxRepeat = (int) $fieldInfos['maxRepeat'];
                $field->result = $fieldInfos['result'];
                $field->type = $fieldInfos['type'];
                $field->styleType = strtoupper($fieldInfos['displayType']);
                $field->valueList = @$fieldInfos['valueList'];
                $field->repetitionStart = @$fieldInfos['repetitionStart'];
                $field->repetitionEnd = @$fieldInfos['repetitionEnd'];
                if ($fieldInfos['notEmpty'] === 'yes') {
                    $field->validationRules[FileMaker::RULE_NOTEMPTY] = true;
                    $field->validationMask |= FileMaker::RULE_NOTEMPTY;
                }
                if ($fieldInfos['numeric'] === 'yes') {
                    $field->validationRules[FileMaker::RULE_NUMERICONLY] = true;
                    $field->validationMask |= FileMaker::RULE_NUMERICONLY;
                }
                if (array_key_exists('maxCharacters', $fieldInfos)) {
                    $field->maxCharacters = (int) $fieldInfos['maxCharacters'];
                    $field->validationRules[FileMaker::RULE_MAXCHARACTERS] = true;
                    $field->validationMask |= FileMaker::RULE_MAXCHARACTERS;
                }
                if ($fieldInfos['fourDigitYear'] === 'yes') {
                    $field->validationRules[FileMaker::RULE_FOURDIGITYEAR] = true;
                    $field->validationMask |= FileMaker::RULE_FOURDIGITYEAR;
                }
                if ($fieldInfos['timeOfDay'] === 'yes') {
                    $field->validationRules[FileMaker::RULE_TIMEOFDAY] = true;
                    $field->validationMask |= FileMaker::RULE_TIMEOFDAY;
                }
                if ($fieldInfos['fourDigitYear'] === 'no' && $fieldInfos['result'] === 'timestamp') {
                    $field->validationRules[FileMaker::RULE_TIMESTAMP_FIELD] = true;
                    $field->validationMask |= FileMaker::RULE_TIMESTAMP_FIELD;
                }
                if ($fieldInfos['fourDigitYear'] === 'no' && $fieldInfos['result'] === 'date') {
                    $field->validationRules[FileMaker::RULE_DATE_FIELD] = true;
                    $field->validationMask |= FileMaker::RULE_DATE_FIELD;
                }
                if ($fieldInfos['timeOfDay'] === 'no' && $fieldInfos['result'] === 'time') {
                    $field->validationRules[FileMaker::RULE_TIME_FIELD] = true;
                    $field->validationMask |= FileMaker::RULE_TIME_FIELD;
                }
                $layout->fields[$field->getName()] = $field;
            }
            foreach ($this->parsedResult['portalMetaData'] as $relatedSetName => $fields) {
                $relatedSet = new RelatedSet($layout);
                $relatedSet->name = $relatedSetName;
                foreach ($fields as $fieldInfos) {
                    $field = new Field($layout);
                    $field->name = $fieldInfos['name'];
                    $field->autoEntered = (bool) ($fieldInfos['autoEnter'] === 'yes');
                    $field->global = (bool) ($fieldInfos['global'] === 'yes');
                    $field->maxRepeat = (int) $fieldInfos['maxRepeat'];
                    $field->result = $fieldInfos['result'];
                    $field->type = $fieldInfos['type'];
                    if ($fieldInfos['notEmpty'] === 'yes') {
                        $field->validationRules[FileMaker::RULE_NOTEMPTY] = true;
                        $field->validationMask |= FileMaker::RULE_NOTEMPTY;
                    }
                    if ($fieldInfos['numeric'] === 'yes') {
                        $field->validationRules[FileMaker::RULE_NUMERICONLY] = true;
                        $field->validationMask |= FileMaker::RULE_NUMERICONLY;
                    }
                    if (array_key_exists('maxCharacters', $fieldInfos)) {
                        $field->maxCharacters = (int) $fieldInfos['maxCharacters'];
                        $field->validationRules[FileMaker::RULE_MAXCHARACTERS] = true;
                        $field->validationMask |= FileMaker::RULE_MAXCHARACTERS;
                    }
                    if ($fieldInfos['fourDigitYear'] === 'yes') {
                        $field->validationRules[FileMaker::RULE_FOURDIGITYEAR] = true;
                        $field->validationMask |= FileMaker::RULE_FOURDIGITYEAR;
                    }
                    if ($fieldInfos['timeOfDay'] === 'yes' || $fieldInfos['result'] === 'time') {
                        $field->validationRules[FileMaker::RULE_TIMEOFDAY] = true;
                        $field->validationMask |= FileMaker::RULE_TIMEOFDAY;
                    }
                    if ($fieldInfos['fourDigitYear'] === 'no' && $fieldInfos['result'] === 'timestamp') {
                        $field->validationRules[FileMaker::RULE_TIMESTAMP_FIELD] = true;
                        $field->validationMask |= FileMaker::RULE_TIMESTAMP_FIELD;
                    }
                    if ($fieldInfos['fourDigitYear'] === 'no' && $fieldInfos['result'] === 'date') {
                        $field->validationRules[FileMaker::RULE_DATE_FIELD] = true;
                        $field->validationMask |= FileMaker::RULE_DATE_FIELD;
                    }
                    if ($fieldInfos['timeOfDay'] === 'no' && $fieldInfos['result'] === 'time') {
                        $field->validationRules[FileMaker::RULE_TIME_FIELD] = true;
                        $field->validationMask |= FileMaker::RULE_TIME_FIELD;
                    }
                    $relatedSet->fields[$field->getName()] = $field;
                }
                $layout->relatedSets[$relatedSet->getName()] = $relatedSet;
            }
        }

        if (array_key_exists('valueLists', $this->parsedResult)){
            foreach ($this->parsedResult['valueLists'] as $valueList) {
                $layout->valueLists[$valueList['name']] = [];
                $layout->valueListTwoFields[$valueList['name']] = [];
                foreach ($valueList['values'] as $value) {
                    $layout->valueLists[$valueList['name']][$value['value']] = $value['value'];
                    $layout->valueListTwoFields[$valueList['name']][$value['value']] = $value['displayValue'];
                }
            }
        }
        $this->layout = $layout;
        return true;
    }

    private function parseFields($rawFields)
    {
        $fields = [];
        foreach ($rawFields as $fieldName => $value) {
            preg_match('/(?<name>.*)\(?(?<repetition>\d?)\)?$/U', $fieldName, $matches);
            if (!isset($fields[$matches['name']])) {
                $fields[$matches['name']] = [];
            }
            $index = !empty($matches['repetition']) ? $matches['repetition']-1 : 0;
            $fields[$matches['name']][$index] = $value;
        }
        return $fields;
    }
}