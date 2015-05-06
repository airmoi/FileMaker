<?php

namespace airmoi\FileMaker\Implementation;

class FileMaker_Record_Implementation {

    private $_fields = array();
    private $_modifiedFields = array();
    private $_recordId;
    private $_modificationId;
    private $_layout;
    private $_fm;
    private $_relatedSets = array();
    private $_parent = null;

    public function __construct($layout) {
        $this->_layout = $layout;
        $this->_fm = $layout->_impl->_fm;
    }

    public function getLayout() {
        return $this->_layout;
    }

    public function getFields() {
        return $this->_layout->listFields();
    }

    public function getField($fieldName, $repetition = 0) {
        if (!isset($this->_fields[$fieldName])) {
            $this->_fm->log('Field "' . $fieldName . '" not found.', FILEMAKER_LOG_INFO);
            return "";
        }
        if (!isset($this->_fields[$fieldName][$repetition])) {
            $this->_fm->log('Repetition "' . (int) $repetition . '" does not exist for "' . $fieldName . '".', FILEMAKER_LOG_INFO);
            return "";
        }
        return htmlspecialchars($this->_fields[$fieldName][$repetition]);
    }

    public function getFieldUnencoded($fieldName, $repetition = 0) {
        if (!isset($this->_fields[$fieldName])) {
            $this->_fm->log('Field "' . $fieldName . '" not found.', FILEMAKER_LOG_INFO);
            return "";
        }
        if (!isset($this->_fields[$fieldName][$repetition])) {
            $this->_fm->log('Repetition "' . (int) $repetition . '" does not exist for "' . $fieldName . '".', FILEMAKER_LOG_INFO);
            return "";
        }
        return $this->_fields[$fieldName][$repetition];
    }

    public function getFieldAsTimestamp($fieldName, $repetition = 0) {
        $value = $this->getField($fieldName, $repetition);
        if (FileMaker::isError($value)) {
            return $value;
        }
        $fieldType = $this->_layout->getField($fieldName);
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

    public function setField($fieldName, $value, $repetition = 0) {
        $this->_fields[$fieldName][$repetition] = $value;
        $this->_modifiedFields[$fieldName][$repetition] = true;
        return $value;
    }

    public function setFieldFromTimestamp($fieldName, $value, $repetition = 0) {
        $fieldType = $this->_layout->getField($fieldName);
        if (FileMaker::isError($fieldType)) {
            return $fieldType;
        }
        switch ($fieldType->getResult()) {
            case 'date':
                return $this->setField($fieldName, date('m/d/Y', $value), $repetition);
            case 'time':
                return $this->setField($fieldName, date('H:i:s', $value), $repetition);
            case 'timestamp':
                return $this->setField($fieldName, date('m/d/Y H:i:s', $value), $repetition);
        }
        return new FileMaker_Error($this->_fm, 'Only time, date, and timestamp fields can be set to the value of a timestamp.');
    }

    public function getRecordId() {
        return $this->_recordId;
    }

    public function getModificationId() {
        return $this->_modificationId;
    }

    public function getRelatedSet($relatedSetName) {
        if (empty($this->_relatedSets[$relatedSetName])) {
           return new FileMaker_Error($this->_fm, 'Related set "' . $relatedSetName . '" not present.'); 
        }
        return $this->_relatedSets[$relatedSetName];
    }

    public function newRelatedRecord($parentRecord, $relatedSetName) {
        $relatedSetInfos = $this->_layout->getRelatedSet($relatedSetName);
        if (FileMaker::isError($relatedSetInfos)) {
            return $relatedSetInfos;
        }
        $record = new FileMaker_Record($relatedSetInfos);
        $record->_impl->_parent = $parentRecord;
        return $record;
    }

    public function getParent() {
        return $this->_parent;
    }

    public function validate($V972bf3f0 = null) {
        $command = $this->_fm->newAddCommand($this->_layout->getName(), $this->_fields);
        return $command->validate($V972bf3f0);
    }

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

}
