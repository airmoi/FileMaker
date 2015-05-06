<?php
namespace airmoi\FileMaker\Command;
use airmoi\FileMaker\FileMaker;
use airmoi\FileMaker\Parser\FMResultSet;
use airmoi\FileMaker\Object\Result;
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
 * Base Command class. Represents commands that add records, delete records, 
 * duplicate records, edit records, perform find requests, and perform 
 * ScriptMaker scripts.
 *
 * @package FileMaker
 */
class Command
{
    /**
     * Implementation. This is the object that actually implements the
     * command base.
     *
     * @var FileMaker
     * @access private
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

    public function __construct(FileMaker $fm, $layout){
        $this->_fm = $fm;
        $this->_layout = $layout;
        $this->_recordClass = $fm->getProperty('recordClass');
    }
    /**
     * Requests that the command's result be returned in a layout different 
     * from the current layout.
     *
     * @param string $layout Layout to return results in.
     */
    public function setResultLayout($layout)
    {
        $this->_resultLayout = $layout;
    }

    /**
     * Sets a ScriptMaker script to be run after the Find result set is 
     * generated and sorted.
     *
     * @param string $scriptName Name of the ScriptMaker script to run.
     * @param string $scriptParameters Any parameters to pass to the script.
     */
    public function setScript($scriptName, $scriptParameters = null)
    {
        $this->_script = $scriptName;
        $this->_scriptParams = $scriptParams;
    }

    /**
     * Sets a ScriptMaker script to be run before performing a command.
     *
     * @param string $scriptName Name of the ScriptMaker script to run.
     * @param string $scriptParameters Any parameters to pass to the script.
     */
    public function setPreCommandScript($scriptName, $scriptParameters = null)
    {
         $this->_preReqScript = $scriptName;
        $this->_preReqScriptParams = $scriptParams;
    }

    /**
     * Sets a ScriptMaker script to be run after performing a Find command, 
     * but before sorting the result set.
     *
     * @param string $scriptName Name of the ScriptMaker script to run.
     * @param string $scriptParameters Any parameters to pass to the script.
     */
    public function setPreSortScript($scriptName, $scriptParameters = null)
    {
         $this->_preSortScript = $scriptName;
        $this->_preSortScriptParams = $scriptParams;
    }

    /**
     * Sets the PHP class that the API instantiates to represent records 
     * returned in any result set. 
     * 
     * The default is to use the provided FileMaker_Record class. Any 
     * substitute classes must provide the same API that FileMaker_Record does, 
     * either by extending it or re-implementing the necessary methods. The 
     * user is responsible for defining any custom class before the API 
     * needs to instantiate it.
     *
     * @param string $className Name of the class to represent records.
     */
    public function setRecordClass($className)
    {
        $this->_recordClass = $className;
    }

    /**
     * Pre-validates either a single field or the entire command.
     *
     * This method uses the pre-validation rules that are enforceable by the 
     * PHP engine -- for example, type rules, ranges, and four-digit dates. 
     * Rules such as "unique" or "existing," or validation by calculation 
     * field, cannot be pre-validated.
     *
     * If you pass the optional $fieldName argument, only that field is 
     * pre-validated. Otherwise, the command is pre-validated as if execute() 
     * were called with "Enable record data pre-validation" selected in 
     * FileMaker Server Admin Console. If pre-validation passes, validate() 
     * returns TRUE. If pre-validation fails, then validate() returns a  
     * FileMaker_Error_Validation object containing details about what failed 
     * to pre-validate.
     *
     * @param string $fieldName Name of field to pre-validate. If empty, 
     *        pre-validates the entire command.
     *
     * @return boolean|FileMaker_Error_Validation TRUE, if pre-validation 
     *         passes. Otherwise, an Error Validation object.
     */
    public function validate($fieldName = null)
    {
        if (!is_a($this, 'Add') && !is_a($this, 'Edit')) {
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

    /**
     * Executes the command.
     *
     * @return FileMaker_Result Result object.
     */
    public function execute()
    {
        return $this->execute();
    }

    /**
     * Sets the record ID for this command. 
     * 
     * For Edit, Delete, and Duplicate commands, a record ID must be specified.
     * It is also possible to find a single record by specifying its record
     * ID. This method is ignored by Add and FindAny commands.
     *
     * @param string $recordId ID of record this command acts upon.
     */
    public function setRecordId($recordId)
    {
        $this->_recordId = $recordId;
    }

    /**
     * 
     * @param string $xml
     * @return Result
     */
    private function _getResult($xml) {
        $parser = new FMResultSet($this->_fm);
        $parseResult = $parser->parse($xml);
        if (FileMaker :: isError($parseResult)) {
            return $parseResult;
        }
        $result = new Result($this->_fm);
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
