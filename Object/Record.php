<?php

namespace airmoi\FileMaker\Object;

use airmoi\FileMaker\FileMaker;
use airmoi\FileMaker\FileMakerException;

/**
 * FileMaker API for PHP
 *
 * @package FileMaker
 *
 * Copyright © 2005-2007, FileMaker, Inc. All rights reserved.
 * NOTE: Use of this source code is subject to the terms of the FileMaker
 * Software License which accompanies the code. Your use of this source code
 * signifies your agreement to such license terms and conditions. Except as
 * expressly granted in the Software License, no other copyright, patent, or
 * other intellectual property license or right is granted, either expressly or
 * by implication, by FileMaker.
 */
/**
 * @ignore Load delegate.
 */
//require_once dirname(__FILE__) . '/Implementation/RecordImpl.php';

/**
 * Default Record class that represents each record of a result set. 
 * 
 * From a Record object, you can get field data, edit and delete the record, 
 * get its parent record, get its related record set, and create related 
 * records. 
 * 
 * Instead of this class, you can specify a different class to use for Record 
 * objects. To specify the record class to use, open the 
 * FileMaker/conf/filemaker-api.php configuration file where the API is 
 * installed. Then set $__FM_CONFIG['recordClass'] to the name of the record 
 * class to use. The class you specify should be a subclass of the 
 * Record base class or encapsulate its functionality. In PHP 5, 
 * this class would implement an interface that alternate classes would be 
 * required to implement as well. 
 * 
 * @package FileMaker
 */
class Record {

    public $fields = array();
    public $recordId;
    public $modificationId;
    public $relatedSetName = null;
    /**
     *
     * @var FileMaker
     */
    public $fm;
    public $relatedSets = array();
    
    /**
     *
     * @var Record
     */
    public $parent = null;
    
    private $_modifiedFields = array();
    
    /**
     *
     * @var Layout|RelatedSet 
     */
    public $layout;
    /**
     * Record object constructor.
     *
     * @param Layout|RelatedSet Specify either the Layout 
     *        object associated with this record or the Related Set object 
     *        that this record is a member of.
     */
    public function __construct($layout) {
        $this->layout = $layout;
        $this->fm = $layout->fm;
    }

    /**
     * Returns the layout this record is associated with.
     *
     * @return Layout This record's layout.
     */
    public function getLayout() {
        return $this->layout;
    }

    /**
     * Returns a list of the names of all fields in the record. 
     *
     * Only the field names are returned. If you need additional 
     * information, examine the Layout object provided by the 
     * parent object's {@link Result::getLayout()} method.  
     *
     * @return array List of field names as strings.
     */
    public function getFields() {
        return $this->layout->listFields();
    }

    /**
     * Returns the HTML-encoded value of the specified field.
     *
     * This method converts some special characters in the field value to 
     * HTML entities. For example, '&', '"', '<', and '>' are converted to 
     * '&amp;', '&quot;', '&lt;', and '&gt;', respectively.
     *
     * @param string $field Name of field.
     * @param integer $repetition Field repetition number to get. 
     *        Defaults to the first repetition.
     *
     * @return string Encoded field value.
     */
    public function getField($field, $repetition = 0, $unencoded = true) {
        if( !is_null($this->parent) && !strpos($field, '::')){
            $field = $this->relatedSetName. '::' . $field;
        }
        if (!isset($this->fields[$field])) {
            //$this->_fm->log('Field "' . $field . '" not found.', FileMaker::LOG_INFO);
            return "";
        }
        if (!isset($this->fields[$field][$repetition])) {
            //$this->_fm->log('Repetition "' . (int) $repetition . '" does not exist for "' . $field . '".', FileMaker::LOG_INFO);
            return "";
        }
        return htmlspecialchars($this->fields[$field][$repetition]);
    }    
    
    /**
     * Returns the two field value list associated with the given field in Record's layout.
     * 
     * @param string $fieldName Field's Name 
     * @return array
     * @see Layout::getValueListTwoFields
     */
    public function getFieldValueListTwoFields($fieldName) {
        if( !is_null($this->parent) && !strpos($fieldName, '::')){
            $fieldName = $this->relatedSetName. '::' . $fieldName;
        }
        if (!isset($this->fields[$fieldName])) {
            //$this->_fm->log('Field "' . $field . '" not found.', FileMaker::LOG_INFO);
            return [];
        }
        
        //Force load extendedInfos as Field's valueList property is not set until extended infos are retrieved
        $this->layout->loadExtendedInfo($this->recordId);
        
        //Get the value list if field has one
        if($this->layout->fields[$fieldName]->valueList !== null){
            return $this->layout->getValueListTwoFields($this->layout->fields[$fieldName]->valueList, $this->recordId );
        }
        return [];
    }

    /**
     * Returns the unencoded value of the specified field.
     *
     * This method does not convert special characters in the field value to 
     * HTML entities.
     * @deprecated since version 2.0 use getField($field, $repetition = 0, $unencoded = true) instead
     * @param string $field Name of field.
     * @param integer $repetition Field repetition number to get. 
     *        Defaults to the first repetition.
     *
     * @return string Unencoded field value.
     */
    public function getFieldUnencoded($field, $repetition = 0) {
        return $this->getField($field, $repetition, true);
    }

    /**
     * Returns the value of the specified field as a UNIX 
     * timestamp. 
     * 
     * If the field is a date field, the timestamp is
     * for the field date at midnight. It the field is a time field,
     * the timestamp is for that time on January 1, 1970. Timestamp
     * (date and time) fields map directly to the UNIX timestamp. If the
     * specified field is not a date or time field, or if the timestamp
     * generated would be out of range, then this method throws a
     * FileMakerException object instead.
     * 
     * @param string $field Name of the field.
     * @param integer $repetition Field repetition number to get. 
     *        Defaults to the first repetition.
     *
     * @return integer Timestamp value.
     * @throws FileMakerException
     */
    public function getFieldAsTimestamp($field, $repetition = 0) {
        $value = $this->getField($field, $repetition);
        $fieldType = $this->layout->getField($field);
        switch ($fieldType->getResult()) {
            case 'date':
                $explodedValue = explode('/', $value);
                if (count($explodedValue) != 3) {
                    throw new FileMakerException($this->fm, 'Failed to parse "' . $value . '" as a FileMaker date value.');
                }
                $result = @mktime(0, 0, 0, $explodedValue[0], $explodedValue[1], $explodedValue[2]);
                if ($result === false) {
                    throw new FileMakerException($this->fm, 'Failed to convert "' . $value . '" to a UNIX timestamp.');
                }
                break;
            case 'time':
                $explodedValue = explode(':', $value);
                if (count($explodedValue) != 3) {
                    throw new FileMakerException($this->fm, 'Failed to parse "' . $value . '" as a FileMaker time value.');
                }
                $result = @mktime($explodedValue[0], $explodedValue[1], $explodedValue[2], 1, 1, 1970);
                if ($result === false) {
                    throw new FileMakerException($this->fm, 'Failed to convert "' . $value . '" to a UNIX timestamp.');
                }
                break;
            case 'timestamp':
                $result = @strtotime($value);
                if ($result === false) {
                    throw new FileMakerException($this->fm, 'Failed to convert "' . $value . '" to a UNIX timestamp.');
                }
                break;
            default:
                throw new FileMakerException($this->fm, 'Only time, date, and timestamp fields can be converted to UNIX timestamps.');
                break;
        }
        return $result;
    }

    /**
     * Sets the value of $field.
     *
     * @param string $field Name of the field.
     * @param string $value New value of the field.
     * @param integer $repetition Field repetition number to set. 
     *        Defaults to the first repetition.
     */
    public function setField($field, $value, $repetition = 0) {
        
        if( !is_null($this->parent) && !strpos($field, '::')){
            $field = $this->relatedSetName. '::' . $field;
        }
        if ( array_search($field, $this->getFields()) === false)
                throw new FileMakerException($this->fm, 'Field "'.$field.'" is missing');
        
        $this->fields[$field][$repetition] = $value;
        $this->_modifiedFields[$field][$repetition] = true;
        return $value;
    }

    /**
     * Sets the new value for a date, time, or timestamp field from a
     * UNIX timestamp value. 
     *
     * If the field is not a date or time field, then returns an error. 
     * Otherwise, returns TRUE.
     *
     * If layout data for the target of this command has not already 
     * been loaded, calling this method loads layout data so that
     * the type of the field can be checked.
     *
     * @param string $field Name of the field to set.
     * @param string $timestamp Timestamp value.
     * @param integer $repetition Field repetition number to set. 
     *        Defaults to the first repetition.
     */
    public function setFieldFromTimestamp($field, $timestamp, $repetition = 0) {
        $fieldType = $this->layout->getField($field);
        if (FileMaker::isError($fieldType)) {
            return $fieldType;
        }
        switch ($fieldType->getResult()) {
            case 'date':
                return $this->setField($field, date('m/d/Y', $value), $repetition);
            case 'time':
                return $this->setField($field, date('H:i:s', $value), $repetition);
            case 'timestamp':
                return $this->setField($field, date('m/d/Y H:i:s', $value), $repetition);
        }
        throw new FileMakerException($this->fm, 'Only time, date, and timestamp fields can be set to the value of a timestamp.');
    }

    /**
     * Returns the record ID of this object.
     *
     * @return string Record ID.
     */
    public function getRecordId() {
        return $this->recordId;
    }

    /**
     * Returns the modification ID of this record.
     * 
     * The modification ID is an incremental counter that specifies the current 
     * version of a record. See the {@link Edit::setModificationId()} 
     * method.
     *
     * @return integer Modification ID.
     */
    public function getModificationId() {
        return $this->modificationId;
    }

    /**
     * Returns any Record objects in the specified portal or throw a FileMakerException
     * object if there are no related records
     *
     * @param string $relatedSet Name of the portal to return records from.
     *
     * @return Record[] Array of Record objects from $relatedSet.
     * @throws FileMakerException
     */
    public function getRelatedSet($relatedSet) {
        if (empty($this->relatedSets[$relatedSet])) {
            throw new FileMakerException($this->fm, 'Related set "' . $relatedSet . '" not present.');
        }
        return $this->relatedSets[$relatedSet];
    }

    /**
     * Creates a new record in the specified portal.
     *
     * @param string $relatedSet Name of the portal to create a new record in.
     *
     * @return Record A new, blank record.
     * @throws FileMakerException
     */
    public function newRelatedRecord($relatedSet) {
        $relatedSetInfos = $this->layout->getRelatedSet($relatedSet);
        $record = new Record($relatedSetInfos);
        $record->setParent($this);
        $record->relatedSetName = $relatedSet;
        return $record;
    }

    /**
     * Returns the parent record, if this record is a child record in a portal.
     *
     * @return Record Parent record.
     */
    public function getParent() {
        return $this->parent;
    }

    /**
     * Set the parent record, if this record is a child record in a portal.
     *
     * @return Record Parent record.
     */
    public function setParent($record) {
        $this->parent = $record;
    }

    /**
     * Pre-validates either a single field or the entire record.
     * 
     * This method uses the pre-validation rules that are enforceable by the 
     * PHP engine -- for example, type rules, ranges, and four-digit dates. 
     * Rules such as "unique" or "existing," or validation by calculation 
     * field, cannot be pre-validated.
     *
     * If you pass the optional $fieldName argument, only that field is 
     * pre-validated. Otherwise, the record is pre-validated as if commit()
     * were called with "Enable record data pre-validation" selected in
     * FileMaker Server Admin Console. If pre-validation passes, validate() 
     * returns TRUE. If pre-validation fails, then validate() throws a 
     * FileMakerValidationException object containing details about what failed 
     * to pre-validate.
     *
     * @param string $fieldName Name of field to pre-validate. If empty, 
     *        pre-validates the entire record.
     *
     * @return boolean TRUE, if pre-validation passes for $value.
     * @throws \airmoi\FileMaker\FileMakerValidationException
     */
    public function validate($fieldName = null) {
        $command = $this->fm->newAddCommand($this->layout->getName(), $this->fields);
        return $command->validate($fieldName);
    }

    /**
     * Saves any changes to this record in the database on the Database Server.
     *
     * @return boolean TRUE, if successful.
     *         object.
     * @throws FileMakerException
     */
    public function commit() {
        if ($this->fm->getProperty('prevalidate')) {
            $validation = $this->validate();
            if (FileMaker::isError($validation)) {
                return $validation;
            }
        }
        if (is_null($this->parent)) {
            if ($this->recordId) {
                return $this->_commitEdit();
            } else {
                return $this->_commitAdd();
            }
        } else {
            if (!$this->parent->getRecordId()) {
                throw new FileMakerException($this->fm, 'You must commit the parent record first before you can commit its children.');
            }
            if ($this->recordId) {
                return $this->_commitEditChild();
            } else {
                return $this->_commitAddChild();
            }
        }
    }

    /**
     * Deletes this record from the database on the Database Server.
     *
     * @return Result Response object.
     * @throws FileMakerException
     */
    public function delete() {
        if (empty($this->recordId)) {
            throw new FileMakerException($this->fm, 'You cannot delete a record that does not exist on the server.');
        }
        if ($this->parent) {
            $editCommand = $this->fm->newEditCommand($this->parent->layout->getName(), $this->parent->recordId, []);
            $editCommand->_setdeleteRelated($this->layout->getName() . "." . $this->recordId);

            return $editCommand->execute();
        } else {
            $layoutName = $this->layout->getName();

            $editCommand = $this->fm->newDeleteCommand($layoutName, $this->recordId);
            return $editCommand->execute();
        }
    }

    /**
     * Gets a specific related record. 
     *
     * @access private
     *
     * @param string $relatedSetName Name of the portal.
     * @param string $recordId Record ID of the record in the portal.
     * 
     * @return Record Record object.
     * @throws FileMakerException
     */
    public function getRelatedRecordById($relatedSetName, $recordId) {
        try {
            $relatedSet = $this->getRelatedSet($relatedSetName);
        }
        catch (FileMakerException $e) {
            throw new FileMakerException($this->fm, 'Related set "' . $relatedSetName . '" not present.'); 
        }
        
        foreach ($relatedSet as $record) {
            if ($record->getRecordId() == $recordId) {
                return $record;
            }
        }
        throw new FileMakerException($this->fm, 'Record not present.');

    }
    
    /**
     * 
     * @return boolean TRUE on success
     * @throws FileMakerException
     */
    private function _commitAdd() {
        $addCommand = $this->fm->newAddCommand($this->layout->getName(), $this->fields);
        $result = $addCommand->execute();
        $records = $result->getRecords();
        return $this->_updateFrom($records[0]);
    }

    /**
     * 
     * @return boolean TRUE on success
     * @throws FileMakerException
     */
    private function _commitEdit() {
        $editedFields=[];
        foreach ($this->fields as $fieldName => $repetitions) {
            foreach ($repetitions as $repetition => $value) {
                if (isset($this->_modifiedFields[$fieldName][$repetition])) {
                    $editedFields[$fieldName][$repetition] = $value;
                }
            }
        }
        $command = $this->fm->newEditCommand($this->layout->getName(), $this->recordId, $editedFields);
        $result = $command->execute();
        $records = $result->getRecords();
        return $this->_updateFrom($records[0]);
    }

     /**
     * 
     * @return boolean TRUE on success
     * @throws FileMakerException
     */
    private function _commitAddChild() {
        $childs = array();
        foreach ($this->fields as $fieldName => $repetitions) {
            $childs[$fieldName . '.0'] = $repetitions;
        }
        $command = $this->fm->newEditCommand($this->parent->layout->getName(), $this->parent->getRecordId(), $childs);
        $result = $command->execute();
        $records = $result->getRecords();
        $record = $records[0];
        $relatedSet = $record->getRelatedSet($this->layout->getName());
        $lastRecord = array_pop($relatedSet);
        /*
         * Add record to parents relatedSet
         */
        $this->parent->relatedSets[$this->layout->getName()][] = $this;
        return $this->_updateFrom($lastRecord);
    }

     /**
     * 
     * @return boolean TRUE on success
     * @throws FileMakerException
     */
    private function _commitEditChild() {
        $modifiedFields=[];
        foreach ($this->fields as $fieldName => $repetitions) {
            foreach ($repetitions as $repetition => $value) {
                if (!empty($this->_modifiedFields[$fieldName][$repetition])) {
                    $modifiedFields[$fieldName . '.' . $this->recordId][$repetition] = $value;
                }
            }
        }
        $editCommand = $this->fm->newEditCommand($this->parent->layout->getName(), $this->parent->getRecordId(), $modifiedFields);
        $result = $editCommand->execute();
        $records = $result->getRecords();
        $firstRecord = $records[0];
        $relatedSet = $firstRecord->getRelatedSet($this->layout->getName());
        foreach ($relatedSet as $record) {
            if ($record->getRecordId() == $this->recordId) {
                return $this->_updateFrom($record);
                break;
            }
        }
        throw new FileMakerException('Failed to find the updated child in the response.');
    }

    /**
     * 
     * @param Record $record
     * @return boolean
     */
    private function _updateFrom(Record $record) {
        $this->recordId = $record->getRecordId();
        $this->modificationId = $record->getModificationId();
        $this->fields = $record->fields;
        $this->layout = $record->layout;
        $this->relatedSets = & $record->relatedSets;
        $this->_modifiedFields = array();
        return true;
    }

}
