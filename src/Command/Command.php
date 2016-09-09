<?php
/**
 * @copyright Copyright (c) 2016 by 1-more-thing (http://1-more-thing.com) All rights reserved.
 * @licence BSD
 */
namespace airmoi\FileMaker\Command;

use airmoi\FileMaker\FileMaker;
use airmoi\FileMaker\FileMakerException;
use airmoi\FileMaker\FileMakerValidationException;
use airmoi\FileMaker\Parser\FMResultSet;
use airmoi\FileMaker\Object\Result;

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
     * @access public
     */
    public $fm;
    
    /**
     *
     * @var \airmoi\FileMaker\Object\Layout
     */
    protected $_layout;

    protected $_fields = array();
    protected $_resultLayout;
    protected $_script;
    protected $_scriptParams;
    protected $_preReqScript;
    protected $_preReqScriptParams;
    protected $_preSortScript;
    protected $_preSortScriptParams;
    protected $_recordClass;
    protected $_globals = [];
    
    public $recordId;

    /**
     * Command constructor.
     *
     * @param FileMaker $fm
     * @param string    $layout
     */
    public function __construct(FileMaker $fm, $layout){
        $this->fm = $fm;
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
        $this->_scriptParams = $scriptParameters;
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
        $this->_preReqScriptParams = $scriptParameters;
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
        $this->_preSortScriptParams = $scriptParameters;
    }

    /**
     * Sets the PHP class that the API instantiates to represent records 
     * returned in any result set. 
     * 
     * The default is to use the provided \airmoi\FileMaker\Object\Record class. Any 
     * substitute classes must provide the same API that \airmoi\FileMaker\Object\Record does, 
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
     * returns TRUE. If pre-validation fails, then validate() throws a
     * \airmoi\FileMaker\FileMakerValidationException object containing details about what failed
     * to pre-validate.
     *
     * @param string $fieldName Name of field to pre-validate. If empty,
     *                          pre-validates the entire command.
     *
     * @return bool TRUE, if pre-validation passes.
     * @throws FileMakerException
     * @throws FileMakerValidationException
     */
    public function validate($fieldName = null)
    {
        if (!is_a($this, __NAMESPACE__.'\Add') && !is_a($this, __NAMESPACE__.'\Edit')) {
            return true;
        }
        $layout = $this->fm->getLayout($this->_layout);
        $validationErrors = new FileMakerValidationException($this->fm);
        if ($fieldName === null) {
            foreach ($layout->getFields() as $fieldName => $field) {
                if (!isset($this->_fields[$fieldName]) || !count($this->_fields[$fieldName])) {
                    $values = array(
                        0 => null
                    );
                } else {
                    $values = $this->_fields[$fieldName];
                }
                foreach ($values as $value) {
                    try {
                        $field->validate($value);
                    }catch (FileMakerValidationException $e){
                        foreach ( $e->getErrors() as $error ) {
                            $validationErrors->addError($error[0], $error[1], $error[2]);
                        }
                    }
                }
            }
        } else {
            $field = $layout->getField($fieldName);
            if (!isset($this->_fields[$fieldName]) || !count($this->_fields[$fieldName])) {
                $values = array(
                    0 => null
                );
            } else {
                $values = $this->_fields[$fieldName];
            }
            foreach ($values as $value) {
                try {
                        $field->validate($value);
                    }catch (FileMakerValidationException $e){
                        foreach ( $e->getErrors() as $error ) {
                            $validationErrors->addError($error[0], $error[1], $error[2]);
                        }
                    }
            }
        }
        if ( $validationErrors->numErrors() )
            throw $validationErrors;
        return true;
    }

    /**
     * Executes the command.
     *
     * @return Result Result object.
     */
    public function execute()
    {
        
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
        $this->recordId = $recordId;
    }

    /**
     * Set a global field to be define before perfoming the command. 
     * 
     *
     * @param string $fieldName the global field name.
     * @param string $fieldValue value to be set.
     */
    public function setGlobal($fieldName, $fieldValue)
    {
        $this->_globals[$fieldName] = $fieldValue;
    }

    /**
     * 
     * @param string $xml
     * @return Result|FileMakerException
     * @throws FileMakerException
     */
    protected function _getResult($xml) {
        $parser      = new FMResultSet($this->fm);
        $parseResult = $parser->parse($xml);
        if(FileMaker::isError($parseResult)){
            return $parseResult;
        }
        
        $result      = new Result($this->fm);
        $parseResult = $parser->setResult($result, $this->_recordClass);
        if(FileMaker::isError($parseResult)){
            return $parseResult;
        }
        
        return $result;
    }

    protected function _getCommandParams() {
        $queryParams = array(
            '-db' => $this->fm->getProperty('database'
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
        
        foreach ( $this->_globals as $fieldName => $fieldValue ){
            $queryParams[$fieldName.'.global'] = $fieldValue;
        }
        return $queryParams;
    }

}
