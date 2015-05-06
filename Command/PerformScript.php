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
    private $_script;
    private $_scriptParams;

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
    
    function execute() {
        $params = $this->_getCommandParams();
        $params['-findany'] = true;
        $result = $this->_fm->_execute($params);
        if (FileMaker::isError($result)) {
            return $result;
        }
        return $this->_getResult($result);
    }

}
