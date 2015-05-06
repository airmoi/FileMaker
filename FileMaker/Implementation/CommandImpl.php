<?php

namespace airmoi\FileMaker\Implementation;
use airmoi\FileMaker;
use airmoi\FileMaker\Error;

class FileMaker_Command_Implementation {

    /**
     *
     * @var FileMaker_Implementation 
     */
    private $_fm;
    private $_layout;
    private $_resultLayout;
    private $_script;
    private $_scriptParams;
    private $_preReqScript;
    private $_preReqScriptParams;
    private $_preSortScript;
    private $_preSortScriptParams;
    private $_recordClass;
    private $_recordId;

    public function __construct(FileMaker_Implementation $fm, $layout) {
        $this->_fm = $fm;
        $this->_layout = $layout;
        $this->_recordClass = $fm->getProperty('recordClass');
    }

    public function setResultLayout($layout) {
        $this->_resultLayout = $layout;
    }

    public function setScript($scriptName, $scriptParams = null) {
        $this->_script = $scriptName;
        $this->_scriptParams = $scriptParams;
    }

    public function setPreCommandScript($scriptName, $scriptParams = null) {
        $this->_preReqScript = $scriptName;
        $this->_preReqScriptParams = $scriptParams;
    }

    public function setPreSortScript($scriptName, $scriptParams = null) {
        $this->_preSortScript = $scriptName;
        $this->_preSortScriptParams = $scriptParams;
    }

    public function setRecordClass($className) {
        $this->_recordClass = $className;
    }

    public function setRecordId($recordId) {
        $this->_recordId = $recordId;
    }

    public function validate($field = null) {
        if (!is_a($this, 'FileMaker_Command_Add_Implementation') && !is_a($this, 'FileMaker_Command_Edit_Implementation')) {
            return true;
        }
        $layout = $this->_fm->getLayout($this->_layout);
        if (FileMaker :: isError($layout)) {
            return $layout;
        }
        $validationErrors = new FileMaker_Error_Validation($this->_fm);
        if ($field === null) {
            foreach ($layout->getFields() as $field => $properties) {
                if (!isset($this->_fields[$field]) || !count($this->_fields[$field])) {
                    $errors = array(
                        0 => null
                    );
                } else {
                    $errors = $this->_fields[$field];
                }
                foreach ($errors as $error) {
                    $validationErrors = $properties->validate($error, $validationErrors);
                }
            }
        } else {
            $properties = & $layout->getField($field);
            if (FileMaker :: isError($properties)) {
                return $properties;
            }
            if (!isset($this->_fields[$field]) || !count($this->_fields[$field])) {
                $errors = array(
                    0 => null
                );
            } else {
                $errors = $this->_fields[$field];
            }
            foreach ($errors as $error) {
                $validationErrors = $properties->validate($error, $validationErrors);
            }
        }
        return $validationErrors->numErrors() ? $validationErrors : true;
    }

    private function _getResult($xml) {
        $parser = new FileMaker_Parser_FMResultSet($this->_fm);
        $parseResult = $parser->parse($xml);
        if (FileMaker :: isError($parseResult)) {
            return $parseResult;
        }
        $result = new FileMaker_Result($this->_fm);
        $parseResult = $parser->setResult($result, $this->_recordClass);
        if (FileMaker :: isError($parseResult)) {
            return $parseResult;
        }
        return $result;
    }

    function _getCommandParams() {
        $queryParams = array(
            '-db' => $this->_fm->getProperty('database'
            ), '-lay' => $this->_layout);
        
        foreach (array(
                '_script' => '-script',
                '_preReqScript' => '-script.prefind',
                '_preSortScript' => '-script.presort'
                    ) as $varName => $paramName) 
                {
            if ($this->$varName) {
                $queryParams[$paramName] = $this->$varName;
                $varName .= 'Params';
                if ($this->$varName !== null) {
                    $queryParams[$paramName . '.param'] = $this->$varName;
                }
            }
        }
        if ($this->_resultLayout) {
            $queryParams['-lay.response'] = $this->_resultLayout;
        }
        return $queryParams;
    }

}
