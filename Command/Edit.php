<?php
namespace airmoi\FileMaker\Command;
use airmoi\FileMaker\FileMaker;
/**
 * FileMaker API for PHP
 *
 * @package FileMaker
 *
 * Copyright © 2005-2009, FileMaker, Inc. All rights reserved.
 * NOTE: Use of this source code is subject to the terms of the FileMaker
 * Software License which accompanies the code. Your use of this source code
 * signifies your agreement to such license terms and conditions. Except as
 * expressly granted in the Software License, no other copyright, patent, or
 * other intellectual property license or right is granted, either expressly or
 * by implication, by FileMaker.
 */


/**
 * Command class that edits a single record.
 * Create this command with {@link FileMaker::newEditCommand()}.
 *
 * @package FileMaker
 */
class Edit extends Command
{
    private $_fields = array();
    private $_modificationId = null;
    private $_deleteRelated;

    /**
     * Edit command constructor.
     *
     * @ignore
     * @param FileMaker_Implementation $fm FileMaker_Implementation object the 
     *        command was created by.
     * @param string $layout Layout the record is part of.
     * @param string $recordId ID of the record to edit.
     * @param array $values Associative array of field name => value pairs. 
     *        To set field repetitions, use a numerically indexed array for 
     *        the value of a field, with the numeric keys corresponding to the 
     *        repetition number to set.
     */
    public function __construct($fm, $layout, $recordId, $updatedValues = array())
    {
        parent::__construct($fm, $layout);
        $this->_recordId = $recordId;
        $this->_deleteRelated = null;
        foreach ($updateValues as $fieldname => $value) {
            if (!is_array($value)) {
                $value = array(
                    $value
                );
            }
            $this->_fields[$fieldname] = $value;
        }
    }
    
    public function execute() {
        $params = $this->_getCommandParams();
        if (empty($this->_recordId)) {
            $error = new FileMaker_Error($this->_fm, 'Edit commands require a record id.');
            return $error;
        }
        if (!count($this->_fields)) {
            if ($this->_deleteRelated == null) {
                $error = new FileMaker_Error($this->_fm, 'There are no changes to make.');
                return $error;
            }
        }

        if ($this->_fm->getProperty('prevalidate')) {
            $layout = & $this->_fm->getLayout($this->_layout);
            $validationError = new FileMaker_Error_Validation($this->_fm);
            foreach ($layout->getFields() as $field => $infos) {
                if (isset($this->_fields[$field])) {
                    $infos = $this->_fields[$field];
                    foreach ($infos as $values) {
                        $validationError = $infos->validate($values);
                        if (FileMaker :: isError($validationError)) {
                            return $validationError;
                        }
                    }
                }
            }
        }

        $layout = & $this->_fm->getLayout($this->_layout);
        if (FileMaker :: isError($layout)) {
            return $layout;
        }
        $params['-edit'] = true;
        if ($this->_deleteRelated == null) {
            foreach ($this->_fields as $fieldname => $values) {
                if (strpos($fieldname, '.') !== false) {
                    list ($fieldname, $infos) = explode('.', $fieldname, 2);
                    $infos = '.' . $infos;
                } else {
                    $fieldname = $fieldname;
                    $infos = $layout->getField($fieldname);
                    if (FileMaker :: isError($infos)) {
                        return $infos;
                    }
                    if ($infos->isGlobal()) {
                        $infos = '.global';
                    } else {
                        $infos = '';
                    }
                }
                foreach ($values as $repetition => $value) {
                    $params[$fieldname . '(' . ($repetition + 1) . ')' . $infos] = $value;
                }
            }
        }
        if ($this->_deleteRelated != null) {
            $params['-delete.related'] = $this->_deleteRelated;
        }
        $params['-recid'] = $this->_recordId;
        if ($this->_modificationId) {
            $params['-modid'] = $this->_modificationId;
        }
        $result = $this->_fm->_execute($params);
        if (FileMaker :: isError($result)) {
            return $result;
        }
        return $this->_getResult($result);
    }

    /**
     * Sets the new value for a field.
     *
     * @param string $field Name of the field to set.
     * @param string $value Value for the field.
     * @param integer $repetition Field repetition number to set,
     *        Defaults to the first repetition.
     */
    public function setField($field, $value, $repetition = 0)
    {
        $this->_fields[$field][$repetition] = $value;
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
     */
    public function setFieldFromTimestamp($field, $timestamp, $repetition = 0)
    {
        $layout = & $this->_fm->getLayout($this->_layout);
        if (FileMaker :: isError($layout)) {
            return $layout;
        }
        $field = & $layout->getField($fieldname);
        if (FileMaker :: isError($field)) {
            return $field;
        }
        switch ($field->getResult()) {
            case 'date' :
                return $this->setField($fieldname, date('m/d/Y', $timestamp), $repetition);
            case 'time' :
                return $this->setField($fieldname, date('H:i:s', $timestamp), $repetition);
            case 'timestamp' :
                return $this->setField($fieldname, date('m/d/Y H:i:s', $timestamp), $repetition);
        }
        return new FileMaker_Error($this->_fm, 'Only time, date, and timestamp fields can be set to the value of a timestamp.');
    }

    /**
     * Sets the modification ID for this command.
     *
     * Before you edit a record, you can use the 
     * {@link FileMaker_Record::getModificationId()} method to get the record's 
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
        $this->_modificationId = $modificationId;
    }

    private function _setdeleteRelated($relatedRecordId) {
        $this->_deleteRelated = $relatedRecordId;
    }

}
