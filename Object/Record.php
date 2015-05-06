<?php

namespace airmoi\FileMaker\Object;

use airmoi\FileMaker\FileMaker;

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
 * FileMaker_Record base class or encapsulate its functionality. In PHP 5, 
 * this class would implement an interface that alternate classes would be 
 * required to implement as well. 
 * 
 * @package FileMaker
 */
class Record {

    private $_fields = array();
    private $_modifiedFields = array();
    private $_recordId;
    private $_modificationId;
    private $_layout;
    private $_fm;
    private $_relatedSets = array();
    private $_parent = null;

    /**
     * Record object constructor.
     *
     * @param FileMaker_Layout|FileMaker_RelatedSet Specify either the Layout 
     *        object associated with this record or the Related Set object 
     *        that this record is a member of.
     */
    public function __construct(Layout $layout) {
        $this->_layout = $layout;
        $this->_fm = $layout->_fm;
    }

    /**
     * Returns the layout this record is associated with.
     *
     * @return FileMaker_Layout This record's layout.
     */
    public function getLayout() {
        return $this->_layout;
    }

    /**
     * Returns a list of the names of all fields in the record. 
     *
     * Only the field names are returned. If you need additional 
     * information, examine the Layout object provided by the 
     * parent object's {@link FileMaker_Result::getLayout()} method.  
     *
     * @return array List of field names as strings.
     */
    public function getFields() {
        return $this->_layout->listFields();
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
    public function getField($field, $repetition = 0) {
        if (!isset($this->_fields[$field])) {
            $this->_fm->log('Field "' . $field . '" not found.', FILEMAKER_LOG_INFO);
            return "";
        }
        if (!isset($this->_fields[$field][$repetition])) {
            $this->_fm->log('Repetition "' . (int) $repetition . '" does not exist for "' . $field . '".', FILEMAKER_LOG_INFO);
            return "";
        }
        return htmlspecialchars($this->_fields[$field][$repetition]);
    }

    /**
     * Returns the unencoded value of the specified field.
     *
     * This method does not convert special characters in the field value to 
     * HTML entities.
     *
     * @param string $field Name of field.
     * @param integer $repetition Field repetition number to get. 
     *        Defaults to the first repetition.
     *
     * @return string Unencoded field value.
     */
    public function getFieldUnencoded($field, $repetition = 0) {
        if (!isset($this->_fields[$field])) {
            $this->_fm->log('Field "' . $field . '" not found.', FILEMAKER_LOG_INFO);
            return "";
        }
        if (!isset($this->_fields[$field][$repetition])) {
            $this->_fm->log('Repetition "' . (int) $repetition . '" does not exist for "' . $field . '".', FILEMAKER_LOG_INFO);
            return "";
        }
        return $this->_fields[$field][$repetition];
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
     * generated would be out of range, then this method returns a
     * FileMaker_Error object instead.
     * 
     * @param string $field Name of the field.
     * @param integer $repetition Field repetition number to get. 
     *        Defaults to the first repetition.
     *
     * @return integer Timestamp value.
     */
    public function getFieldAsTimestamp($field, $repetition = 0) {
        $value = $this->getField($field, $repetition);
        if (FileMaker::isError($value)) {
            return $value;
        }
        $fieldType = $this->_layout->getField($field);
        if (FileMaker::isError($fieldType)) {
            return $fieldType;
        }
        switch ($fieldType->getResult()) {
            case 'date':
                $explodedValue = explode('/', $value);
                if (count($explodedValue) != 3) {
                    return new FileMaker_Error($this->_fm, 'Failed to parse "' . $value . '" as a FileMaker date value.');
                }
                $result = @mktime(0, 0, 0, $explodedValue[0], $explodedValue[1], $explodedValue[2]);
                if ($result === false) {
                    return new FileMaker_Error($this->_fm, 'Failed to convert "' . $value . '" to a UNIX timestamp.');
                }
                break;
            case 'time':
                $explodedValue = explode(':', $value);
                if (count($explodedValue) != 3) {
                    return new FileMaker_Error($this->_fm, 'Failed to parse "' . $value . '" as a FileMaker time value.');
                }
                $result = @mktime($explodedValue[0], $explodedValue[1], $explodedValue[2], 1, 1, 1970);
                if ($result === false) {
                    return new FileMaker_Error($this->_fm, 'Failed to convert "' . $value . '" to a UNIX timestamp.');
                }
                break;
            case 'timestamp':
                $result = @strtotime($value);
                if ($result === false) {
                    return new FileMaker_Error($this->_fm, 'Failed to convert "' . $value . '" to a UNIX timestamp.');
                }
                break;
            default:
                $result = new FileMaker_Error($this->_fm, 'Only time, date, and timestamp fields can be converted to UNIX timestamps.');
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
        $this->_fields[$field][$repetition] = $value;
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
        $fieldType = $this->_layout->getField($field);
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
        return new FileMaker_Error($this->_fm, 'Only time, date, and timestamp fields can be set to the value of a timestamp.');
    }

    /**
     * Returns the record ID of this object.
     *
     * @return string Record ID.
     */
    public function getRecordId() {
        return $this->_recordId;
    }

    /**
     * Returns the modification ID of this record.
     * 
     * The modification ID is an incremental counter that specifies the current 
     * version of a record. See the {@link FileMaker_Command_Edit::setModificationId()} 
     * method.
     *
     * @return integer Modification ID.
     */
    public function getModificationId() {
        return $this->_modificationId;
    }

    /**
     * Returns any Record objects in the specified portal or a FileMaker_Error
     * object if there are no related records
     *
     * @param string $relatedSet Name of the portal to return records from.
     *
     * @return array Array of FileMaker_Record objects from $relatedSet|FileMaker_Error object.
     */
    public function getRelatedSet($relatedSet) {
        if (empty($this->_relatedSets[$relatedSet])) {
            return new FileMaker_Error($this->_fm, 'Related set "' . $relatedSet . '" not present.');
        }
        return $this->_relatedSets[$relatedSet];
    }

    /**
     * Creates a new record in the specified portal.
     *
     * @param string $relatedSet Name of the portal to create a new record in.
     *
     * @return FileMaker_Record A new, blank record.
     */
    public function newRelatedRecord($relatedSet) {
        $relatedSetInfos = $this->_layout->getRelatedSet($relatedSet);
        if (FileMaker::isError($relatedSetInfos)) {
            return $relatedSetInfos;
        }
        $record = new Record($relatedSetInfos);
        $record->setParent($this);
        return $record;
    }

    /**
     * Returns the parent record, if this record is a child record in a portal.
     *
     * @return FileMaker_Record Parent record.
     */
    public function getParent() {
        return $this->_parent;
    }

    /**
     * Set the parent record, if this record is a child record in a portal.
     *
     * @return FileMaker_Record Parent record.
     */
    public function setParent($record) {
        $this->_parent = $record;
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
     * returns TRUE. If pre-validation fails, then validate() returns a 
     * FileMaker_Error_Validation object containing details about what failed 
     * to pre-validate.
     *
     * @param string $fieldName Name of field to pre-validate. If empty, 
     *        pre-validates the entire record.
     *
     * @return boolean|FileMaker_Error_Validation TRUE, if pre-validation 
     *         passes for $value. Otherwise, an Error Validation object.
     */
    public function validate($fieldName = null) {
        $command = $this->_fm->newAddCommand($this->_layout->getName(), $this->_fields);
        return $command->validate($fieldName);
    }

    /**
     * Saves any changes to this record in the database on the Database Server.
     *
     * @return boolean|FileMaker_Error TRUE, if successful. Otherwise, an Error
     *         object.
     */
    public function commit() {
        if ($this->_fm->getProperty('prevalidate')) {
            $validation = $this->validate();
            if (FileMaker::isError($validation)) {
                return $validation;
            }
        }
        if (is_null($this->_parent)) {
            if ($this->_recordId) {
                return $this->_commitEdit();
            } else {
                return $this->_commitAdd();
            }
        } else {
            if (!$this->_parent->getRecordId()) {
                return new FileMaker_Error($this->_fm, 'You must commit the parent record first before you can commit its children.');
            }
            if ($this->_recordId) {
                return $this->_commitEditChild();
            } else {
                return $this->_commitAddChild();
            }
        }
    }

    /**
     * Deletes this record from the database on the Database Server.
     *
     * @return FileMaker_Result Response object.
     */
    public function delete() {
        if (empty($this->_recordId)) {
            return new FileMaker_Error($this->_fm, 'You cannot delete a record that does not exist on the server.');
        }
        if ($this->_parent) {
            $editCommand = $this->_fm->newEditCommand($this->_parent->_impl->_layout->getName(), $this->_parent->_impl->_recordId, []);
            $editCommand->_impl->_setdeleteRelated($this->_layout->getName() . "." . $this->_recordId);

            return $editCommand->execute();
        } else {
            $layoutName = $this->_layout->getName();

            $editCommand = $this->_fm->newDeleteCommand($layoutName, $this->_recordId);
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
     * @return FileMaker_Response Response object.
     */
    public function getRelatedRecordById($relatedSetName, $recordId) {
        $relatedSet = $this->getRelatedSet($relatedSetName);
        if (FileMaker::IsError($relatedSet)) {

            return new FileMaker_Error($this->_fm, 'Related set "' . $relatedSet . '" not present.');
            
        } else {
            foreach ($relatedSet as $record) {
                if ($record->getRecordId() == $recordId) {
                    return $record;
                }
            }
            return new FileMaker_Error($this->_fm, 'Record not present.');
        }
    }
    
    private function _commitAdd() {
        $addCommand = $this->_fm->newAddCommand($this->_layout->getName(), $this->_fields);
        $result = $addCommand->execute();
        if (FileMaker::isError($result)) {
            return $result;
        }
        $records = $result->getRecords();
        return $this->_updateFrom($records[0]);
    }

    private function _commitEdit() {
        foreach ($this->_fields as $fieldName => $repetitions) {
            foreach ($repetitions as $repetition => $value) {
                if (isset($this->_modifiedFields[$fieldName][$repetition])) {
                    $editedFields[$fieldName][$repetition] = $value;
                }
            }
        }
        $command = $this->_fm->newEditCommand($this->_layout->getName(), $this->_recordId, $editedFields);
        $result = $command->execute();
        if (FileMaker::isError($result)) {
            return $result;
        }
        $records = $result->getRecords();
        return $this->_updateFrom($records[0]);
    }

    private function _commitAddChild() {
        $childs = array();
        foreach ($this->_fields as $fieldName => $repetitions) {
            $childs[$fieldName . '.0'] = $repetitions;
        }
        $command = $this->_fm->newEditCommand($this->_parent->_impl->_layout->getName(), $this->_parent->getRecordId(), $childs);
        $result = $command->execute();
        if (FileMaker::isError($result)) {
            return $result;
        }
        $records = $result->getRecords();
        $record = $records[0];
        $relatedSet = & $record->getRelatedSet($this->_layout->getName());
        $lastRecord = array_pop($relatedSet);
        return $this->_updateFrom($lastRecord);
    }

    private function _commitEditChild() {
        foreach ($this->_fields as $fieldName => $repetitions) {
            foreach ($repetitions as $repetition => $value) {
                if (!empty($this->_modifiedFields[$fieldName][$repetition])) {
                    $modifiedFields[$fieldName . '.' . $this->_recordId][$repetition] = $value;
                }
            }
        }
        $editCommand = $this->_fm->newEditCommand($this->_parent->_impl->_layout->getName(), $this->_parent->getRecordId(), $modifiedFields);
        $result = $editCommand->execute();
        if (FileMaker::isError($result)) {
            return $result;
        }
        $records = & $result->getRecords();
        $firstRecord = & $records[0];
        $relatedSet = & $firstRecord->getRelatedSet($this->_layout->getName());
        foreach ($relatedSet as $record) {
            if ($record->getRecordId() == $this->_recordId) {
                return $this->_updateFrom($record);
                break;
            }
        }
        return new FileMaker_Error('Failed to find the updated child in the response.');
    }

    private function _updateFrom($record) {
        $this->_recordId = $record->getRecordId();
        $this->_modificationId = $record->getModificationId();
        $this->_fields = $record->_impl->_fields;
        $this->_layout = $record->_impl->_layout;
        $this->_relatedSets = & $record->_impl->_relatedSets;
        $this->_modifiedFields = array();
        return true;
    }

}
