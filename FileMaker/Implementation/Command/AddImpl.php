<?php

require_once dirname(__FILE__) . '/../CommandImpl.php';

class FileMaker_Command_Add_Implementation extends FileMaker_Command_Implementation {

    var $_fields = array();

    function FileMaker_Command_Add_Implementation($V0ab34ca9, $Vc6140495, $Vf09cc7ee = array()) {
        FileMaker_Command_Implementation::FileMaker_Command_Implementation($V0ab34ca9, $Vc6140495);
        foreach ($Vf09cc7ee as $V06e3d36f => $V2063c160) {
            if (!is_array($V2063c160)) {
                $V2063c160 = array($V2063c160);
            }
            $this->_fields[$V06e3d36f] = $V2063c160;
        }
    }

    function &execute() {
        if ($this->_fm->getProperty('prevalidate')) {
            $validation = $this->validate();
            if (FileMaker::isError($validation)) {
                return $validation;
            }
        }
        $layout = & $this->_fm->getLayout($this->_layout);
        if (FileMaker::isError($layout)) {
            return $layout;
        }
        $params = $this->_getCommandParams();
        $params['-new'] = true;
        foreach ($this->_fields as $field => $values) {
            if (strpos($field, '.') !== false) {
                list($fieldname, $fieldType) = explode('.', $field, 2);
                $fieldType = '.' . $fieldType;
            } else {
                $fieldname = $field;
                $fieldInfos = $layout->getField($field);
                if (FileMaker::isError($fieldInfos)) {
                    return $fieldInfos;
                }
                if ($fieldInfos->isGlobal()) {
                    $fieldType = '.global';
                } else {
                    $fieldType = '';
                }
            }
            foreach ($values as $repetition => $value) {
                $params[$fieldname . '(' . ($repetition + 1) . ')' . $fieldType] = $value;
            }
        }
        $result = $this->_fm->_execute($params);
        if (FileMaker::isError($result)) {
            return $result;
        }
        return $this->_getResult($result);
    }

    function setField($field, $value, $repetition = 0) {
        $this->_fields[$field][$repetition] = $value;
        return $value;
    }

    function setFieldFromTimestamp($field, $timestamp, $repetition = 0) {
        $layout = & $this->_fm->getLayout($this->_layout);
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
        return new FileMaker_Error($this->_fm, 'Only time, date, and timestamp fields can be set to the value of a timestamp.');
    }

}
