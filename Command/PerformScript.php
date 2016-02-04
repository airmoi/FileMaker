<?php
namespace airmoi\FileMaker\Command;
/**
 * FileMaker API PHP
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
     * @param FileMaker_Implementation $fm FileMaker_Implementation object the 
     *        command was created by.
     * @param string $layout Layout to use for script context.
     * @param string $scriptName Name of the script to run.
     * @param string $scriptParameters Any parameters to pass to the script.
     */
    function __construct($fm, $layout, $scriptName, $scriptParameters = null)
    {
        parent::__construct($fm, $layout);
        $this->_script = $scriptName;
        $this->_scriptParams = $scriptParameters;
    }
    
    /**
     * Sets a range to request only part of the result set.
     *
     * @param integer $skip Number of records to skip past. Default is 0.
     * @param integer $max Maximum number of records to return. 
     *        Default is all.
     */
    public function setRange($skip = 0, $max = null)
    {
         $this->_skip = $skip;
        $this->_max = $max;
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
        return array('skip' => $this->_skip,
            'max' => $this->_max);
    }
    
    /**
     * 
     * @return type
     */
    function execute() {
        $params = $this->_getCommandParams();
        $params['-findany'] = true;
        $this->_setRangeParams($params);
        $cUrlResponse = $this->fm->execute($params);
        return $this->_getResult($cUrlResponse);
    }

    protected function _setRangeParams(&$params) {
        if ($this->_skip) {
            $params['-skip'] = $this->_skip;
        }
        if ($this->_max) {
            $params['-max'] = $this->_max;
        }
    }

}
