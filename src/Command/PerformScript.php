<?php
/**
 * @copyright Copyright (c) 2016 by 1-more-thing (http://1-more-thing.com) All rights reserved.
 * @license BSD
 */
namespace airmoi\FileMaker\Command;

use airmoi\FileMaker\FileMaker;
use airmoi\FileMaker\Object\Result;

/**
 * Command class that performs a ScriptMaker script.
 * Create this command with {@link FileMaker::newPerformScriptCommand()}.
 *
 * @package FileMaker
 */
class PerformScript extends Command
{
    protected $_skip;
    protected $_max;

    protected $_script;
    protected $_scriptParams;

    /**
     * PerformScript command constructor.
     *
     * @ignore
     * @param \airmoi\FileMaker\FileMaker $fm FileMaker object the command was created by.
     * @param string $layout Layout to use for script context.
     * @param string $scriptName Name of the script to run.
     * @param string $scriptParameters Any parameters to pass to the script.
     */
    public function __construct(FileMaker $fm, $layout, $scriptName, $scriptParameters = null)
    {
        parent::__construct($fm, $layout);
        $this->_script       = $scriptName;
        $this->_scriptParams = $scriptParameters;
    }

    /**
     * Sets a range to request only part of the result set.
     *
     * @param integer $skip Number of records to skip past. Default is 0.
     * @param integer $max Maximum number of records to return.
     *        Default is all.
     *
     * @return self
     */
    public function setRange($skip = 0, $max = null)
    {
        $this->_skip = $skip;
        $this->_max  = $max;
        return $this;
    }

    /**
     * Returns the current range settings.
     *
     * @return array Associative array with two keys: 'skip' for
     * the current skip setting, and 'max' for the current maximum
     * number of records. If either key does not have a value, the
     * returned value for that key is NULL.
     */
    public function getRange()
    {
        return array(
            'skip' => $this->_skip,
            'max'  => $this->_max
        );
    }

    /**
     *
     * @return Result
     */
    public function execute()
    {
        $params             = $this->_getCommandParams();
        $params['-findany'] = true;
        $this->_setRangeParams($params);
        return $this->_getResult($this->fm->execute($params));
    }

    protected function _setRangeParams(&$params)
    {
        if ($this->_skip) {
            $params['-skip'] = $this->_skip;
        }
        if ($this->_max) {
            $params['-max'] = $this->_max;
        }
    }
}
