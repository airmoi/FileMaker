<?php
/**
 * @copyright Copyright (c) 2016 by 1-more-thing (http://1-more-thing.com) All rights reserved.
 * @license BSD
 */
namespace airmoi\FileMaker\Command;

use airmoi\FileMaker\FileMaker;
use airmoi\FileMaker\FileMakerException;
use airmoi\FileMaker\FileMakerValidationException;
use airmoi\FileMaker\Helpers\DateFormat;
use airmoi\FileMaker\Object\Result;
use airmoi\FileMaker\Parser\DataApiResult;

/**
 * Command class that edits a single record.
 * Create this command with {@link FileMaker::newEditCommand()}.
 *
 * @package FileMaker
 */
class Edit extends Command
{
    public $recordId;

    protected $modificationId = null;
    protected $deleteRelated;
    protected $useRawData = false;
    protected $relatedSetName = null;

    /**
     * Edit command constructor.
     *
     * @ignore
     * @param FileMaker $fm FileMaker object the command was created by.
     * @param string $layout Layout the record is part of.
     * @param string $recordId ID of the record to edit.
     * @param array $updatedValues Associative array of field name => value pairs.
     *        To set field repetitions, use a numerically indexed array for
     *        the value of a field, with the numeric keys corresponding to the
     *        repetition number to set.
     * @param bool $useRawData Prevent data conversion on setField
     */
    public function __construct(FileMaker $fm, $layout, $recordId, $updatedValues = [], $useRawData = false, $relatedSetName = null)
    {
        parent::__construct($fm, $layout);
        $this->recordId = $recordId;
        $this->deleteRelated = null;
        $this->useRawData = $useRawData;
        $this->relatedSetName = $relatedSetName;
        foreach ($updatedValues as $fieldname => $value) {
            if (!is_array($value)) {
                $this->setField($fieldname, $value, 0);
            } else {
                foreach ($value as $repetition => $repetitionValue) {
                    $this->setField($fieldname, $repetitionValue, $repetition) ;
                }
            }
        }
    }

    /**
     *
     * @return \airmoi\FileMaker\Object\Result|FileMakerException|FileMakerValidationException
     * @throws FileMakerException
     * @throws FileMakerValidationException
     */
    public function execute()
    {
        $params = $this->getCommandParams();
        if (empty($this->recordId)) {
            return $this->fm->returnOrThrowException('Edit commands require a record id.');
        }
        if (!count($this->fields)) {
            if ($this->deleteRelated === null) {
                return $this->fm->returnOrThrowException('There are no changes to make.');
            }
        }

        if ($this->fm->getProperty('prevalidate')) {
            $validation = $this->validate();
            if (FileMaker::isError($validation)) {
                return $validation;
            }
        }

        $layout = $this->fm->getLayout($this->layout);
        if (FileMaker::isError($layout)) {
            return $layout;
        }

        $params['-edit'] = true;
        if ($this->fm->engine == 'dataAPI') {
            $params['-relatedSet'] = $this->relatedSetName;
        }
        if ($this->deleteRelated === null) {
            foreach ($this->fields as $fieldname => $values) {
                $suffix = '';
                if (strpos($fieldname, '.') !== false) {
                    list ($fieldname, $infos) = explode('.', $fieldname, 2);
                    $suffix = '.' . $infos;
                } else {
                    $field = $layout->getField($fieldname);
                    if (FileMaker::isError($field)) {
                        return $field;
                    }
                    if ($field->isGlobal()) {
                        $suffix = '.global';
                    }
                }
                foreach ($values as $repetition => $value) {
                    $params[$fieldname . '(' . ($repetition + 1) . ')' . $suffix] = $value;
                }
            }
        }
        if ($this->deleteRelated !== null) {
            $params['-delete.related'] = $this->deleteRelated;
        }
        $params['-recid'] = $this->recordId;
        if ($this->modificationId) {
            $params['-modid'] = $this->modificationId;
        }
        $result = $this->fm->execute($params);
        return $this->getResult($result);
    }

    protected function getResult($response)
    {
        if ($this->fm->engine == 'cwp') {
            $result = parent::getResult($response);
        } else {
            $parser      = new DataApiResult($this->fm);
            $parseResult = $parser->parse($response);
            if (FileMaker::isError($parseResult)) {
                return $parseResult;
            }
            $result = new Result($this->fm);
            $result->records[] = $this->fm->getRecordById($this->layout, $this->recordId);
        }
        return $result;
    }
    /**
     * Sets the new value for a field.
     *
     * @param string $field Name of the field to set.
     * @param string $value Value for the field.
     * @param integer $repetition Field repetition number to set,
     *        Defaults to the first repetition.
     *
     * @return string|FileMakerException
     * @throws FileMakerException
     */
    public function setField($field, $value, $repetition = 0)
    {
        if(preg_match('/(.*)(\.\d+)$/', $field, $matches)){
            $fieldname = $matches[1];
        } else {
            $fieldname = $field;
        }

        $layout = $this->fm->getLayout($this->layout);
        if (FileMaker::isError($layout)) {
            return $layout;
        }
        $fieldInfos = $layout->getField($fieldname);
        if(FileMaker::isError($fieldInfos)){
            return $fieldInfos;
        }

        $format = FileMaker::isError($fieldInfos) ? null : $fieldInfos->result;

        $dateFormat = $this->fm->getProperty('dateFormat');
        if (!$this->useRawData && $dateFormat !== null && ($format === 'date' || $format === 'timestamp')) {
            try {
                if ($format === 'date') {
                    $value = DateFormat::convert($value, $dateFormat, 'm/d/Y');
                } else {
                    $value = DateFormat::convert($value, $dateFormat . ' H:i:s', 'm/d/Y H:i:s');
                }
            } catch (\Exception $e) {
                return $this->fm->returnOrThrowException(
                    $value . ' could not be converted to a valid timestamp for field '
                    . $field . ' (expected format '. $dateFormat .')'
                );
            }
        }

        $this->fields[$field][$repetition] = $value;
        return $value;
    }

    /**
     * Sets the new value for a date, time, or timestamp field from a
     * UNIX timestamp value.
     *
     * If the field is not a date or time field, then this method returns
     * an Error object. Otherwise, returns TRUE.
     *
     * If layout data for the target of this command has not already
     * been loaded, calling this method loads layout data so that
     * the type of the field can be checked.
     *
     * @param string $field Name of the field to set.
     * @param string $timestamp Timestamp value.
     * @param integer $repetition Field repetition number to set.
     *        Defaults to the first repetition.
     *
     * @return string|FileMakerException
     * @throws FileMakerException
     */
    public function setFieldFromTimestamp($field, $timestamp, $repetition = 0)
    {
        $layout = $this->fm->getLayout($this->layout);
        if (FileMaker::isError($layout)) {
            return $layout;
        }
        $fieldInfos = $layout->getField($field);
        if (FileMaker::isError($fieldInfos)) {
            return $fieldInfos;
        }
        switch ($fieldInfos->getResult()) {
            case 'date':
                return $this->setField($field, date('m/d/Y', $timestamp), $repetition);
            case 'time':
                return $this->setField($field, date('H:i:s', $timestamp), $repetition);
            case 'timestamp':
                return $this->setField($field, date('m/d/Y H:i:s', $timestamp), $repetition);
        }
        return $this->fm->returnOrThrowException(
            'Only time, date, and timestamp fields can be set to the value of a timestamp.'
        );
    }

    /**
     * Sets the modification ID for this command.
     *
     * Before you edit a record, you can use the
     * {@link Record::getModificationId()} method to get the record's
     * modification ID. By specifying a modification ID when you execute an
     * Edit command, you can make sure that you are editing the current version
     * of a record. If the modification ID value you specify does not match the
     * current modification ID value in the database, the Edit command is not
     * allowed and an error code is returned.
     *
     * @param integer $modificationId Modification ID.
     */
    public function setModificationId($modificationId)
    {
        $this->modificationId = $modificationId;
    }

    /**
     * Set the related record ID to delete
     * @param int $relatedRecordId
     */
    public function setDeleteRelated($relatedRecordId)
    {
        $this->deleteRelated = $relatedRecordId;
    }
}
