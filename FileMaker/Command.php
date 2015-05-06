<?php
namespace airmoi\FileMaker;
use airmoi\FileMaker\Implementation as Impl;
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
class FileMaker_Command
{
    /**
     * Implementation. This is the object that actually implements the
     * command base.
     *
     * @var Impl\FileMaker_Command_Implementation
     * @access private
     */
    private $_impl;

    /**
     * Requests that the command's result be returned in a layout different 
     * from the current layout.
     *
     * @param string $layout Layout to return results in.
     */
    public function setResultLayout($layout)
    {
        $this->_impl->setResultLayout($layout);
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
        $this->_impl->setScript($scriptName, $scriptParameters);
    }

    /**
     * Sets a ScriptMaker script to be run before performing a command.
     *
     * @param string $scriptName Name of the ScriptMaker script to run.
     * @param string $scriptParameters Any parameters to pass to the script.
     */
    public function setPreCommandScript($scriptName, $scriptParameters = null)
    {
        $this->_impl->setPreCommandScript($scriptName, $scriptParameters);
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
        $this->_impl->setPreSortScript($scriptName, $scriptParameters);
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
        $this->_impl->setRecordClass($className);
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
        return $this->_impl->validate($fieldName);
    }

    /**
     * Executes the command.
     *
     * @return FileMaker_Result Result object.
     */
    public function execute()
    {
        return $this->_impl->execute();
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
        $this->_impl->setRecordId($recordId);
    }

}
