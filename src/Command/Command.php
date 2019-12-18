<?php
/**
 * @copyright Copyright (c) 2016 by 1-more-thing (http://1-more-thing.com) All rights reserved.
 * @license BSD
 */
namespace airmoi\FileMaker\Command;

use airmoi\FileMaker\FileMaker;
use airmoi\FileMaker\FileMakerException;
use airmoi\FileMaker\FileMakerValidationException;
use airmoi\FileMaker\Parser\DataApiResult;
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
    use CommandTrait;

    protected $fields = [];
    protected $resultLayout;
    protected $script;
    protected $scriptParams;
    protected $preReqScript;
    protected $preReqScriptParams;
    protected $preSortScript;
    protected $preSortScriptParams;
    protected $recordClass;
    protected $globals = [];

    public $recordId;

    /**
     * Command constructor.
     *
     * @param FileMaker $fm
     * @param string    $layout
     */
    public function __construct(FileMaker $fm, $layout)
    {
        $this->fm = $fm;
        $this->layout = $layout;
        $this->recordClass = $fm->getProperty('recordClass');
    }
    /**
     * Requests that the command's result be returned in a layout different
     * from the current layout.
     *
     * @param string $layout Layout to return results in.
     * @return self
     */
    public function setResultLayout($layout)
    {
        $this->resultLayout = $layout;
        return $this;
    }

    /**
     * Sets a ScriptMaker script to be run after the Find result set is
     * generated and sorted.
     *
     * @param string $scriptName Name of the ScriptMaker script to run.
     * @param string $scriptParameters Any parameters to pass to the script.
     * @return self
     */
    public function setScript($scriptName, $scriptParameters = null)
    {
        $this->script = $scriptName;
        $this->scriptParams = $scriptParameters;
        return $this;
    }

    /**
     * Sets a ScriptMaker script to be run before performing a command.
     *
     * @param string $scriptName Name of the ScriptMaker script to run.
     * @param string $scriptParameters Any parameters to pass to the script.
     * @return self
     */
    public function setPreCommandScript($scriptName, $scriptParameters = null)
    {
        $this->preReqScript = $scriptName;
        $this->preReqScriptParams = $scriptParameters;
        return $this;
    }

    /**
     * Sets a ScriptMaker script to be run after performing a Find command,
     * but before sorting the result set.
     *
     * @param string $scriptName Name of the ScriptMaker script to run.
     * @param string $scriptParameters Any parameters to pass to the script.
     * @return self
     */
    public function setPreSortScript($scriptName, $scriptParameters = null)
    {
        $this->preSortScript = $scriptName;
        $this->preSortScriptParams = $scriptParameters;
        return $this;
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
     * @return self
     */
    public function setRecordClass($className)
    {
        $this->recordClass = $className;
        return $this;
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
     * @return FileMakerException|FileMakerValidationException|\airmoi\FileMaker\Object\Layout|bool
     * @throws FileMakerException
     * @throws FileMakerValidationException
     */
    public function validate($fieldName = null)
    {
        if (!is_a($this, Add::class) && !is_a($this, Edit::class)) {
            return true;
        }
        $layout = $this->fm->getLayout($this->layout);
        if (FileMaker::isError($layout)) {
            return $layout;
        }

        $validationErrors = new FileMakerValidationException($this->fm);
        if ($fieldName === null) {
            foreach ($layout->getFields() as $fieldName => $field) {
                if (!isset($this->fields[$fieldName]) || !count($this->fields[$fieldName])) {
                    $values = [0 => null];
                } else {
                    $values = $this->fields[$fieldName];
                }
                foreach ($values as $value) {
                    try {
                        $field->validate($value);
                    } catch (FileMakerValidationException $e) {
                        foreach ($e->getErrors() as $error) {
                            $validationErrors->addError($error[0], $error[1], $error[2]);
                        }
                    }
                }
            }
        } else {
            $field = $layout->getField($fieldName);
            if (FileMaker::isError($field)) {
                return $field;
            }

            if (!isset($this->fields[$fieldName]) || !count($this->fields[$fieldName])) {
                $values = [0 => null];
            } else {
                $values = $this->fields[$fieldName];
            }
            foreach ($values as $value) {
                try {
                        $field->validate($value);
                } catch (FileMakerValidationException $e) {
                    foreach ($e->getErrors() as $error) {
                        $validationErrors->addError($error[0], $error[1], $error[2]);
                    }
                }
            }
        }
        if ($validationErrors->numErrors()) {
            if ($this->fm->getProperty('errorHandling') === 'default') {
                return $validationErrors;
            }
            throw $validationErrors;
        }
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
     * @return self
     */
    public function setRecordId($recordId)
    {
        $this->recordId = $recordId;
        return $this;
    }

    /**
     * Set a global field to be define before perfoming the command.
     *
     *
     * @param string $fieldName the global field name.
     * @param string $fieldValue value to be set.
     * @return self
     */
    public function setGlobal($fieldName, $fieldValue)
    {
        $this->globals[$fieldName] = $fieldValue;
        return $this;
    }

    /**
     * Return defined globals
     *
     * @return array
     */
    public function getGlobals()
    {
        return $this->globals;
    }

    /**
     *
     * @param string $xml
     * @return Result|FileMakerException
     * @throws FileMakerException
     */
    protected function getResult($response, $result = null)
    {
        $parser      = $this->fm->engine == 'cwp' ? new FMResultSet($this->fm) : new DataApiResult($this->fm);
        $parseResult = $parser->parse($response);
        if (FileMaker::isError($parseResult)) {
            return $parseResult;
        }

        if (!$result) {
            $result      = new Result($this->fm);
        }
        $parseResult = $parser->setResult($result, $this->recordClass);
        if (FileMaker::isError($parseResult)) {
            return $parseResult;
        }

        return $result;
    }

    /**
     * Build command params
     * @return array
     */
    protected function getCommandParams()
    {
        $queryParams = [
            '-db'  => $this->fm->getProperty('database'),
            '-lay' => $this->layout
        ];

        foreach ([
                    'script' => '-script',
                    'preReqScript' => '-script.prefind',
                    'preSortScript' => '-script.presort'
                 ] as $varName => $paramName) {
            if ($this->$varName) {
                $queryParams[$paramName] = $this->$varName;
                $varName .= 'Params';
                if ($this->$varName !== null) {
                    $queryParams[$paramName . '.param'] = $this->$varName;
                }
            }
        }
        if ($this->resultLayout) {
            $queryParams['-lay.response'] = $this->resultLayout;
        }

        foreach ($this->globals as $fieldName => $fieldValue) {
            $queryParams[$fieldName.'.global'] = $fieldValue;
        }
        return $queryParams;
    }
}
