<?php
namespace airmoi\FileMaker\Command;
/**
 * FileMaker API for PHP
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
 * Command class that finds all records from a layout. 
 * Create this command with {@link FileMaker::newFindAllCommand()}.
 *
 * @package FileMaker
 */
class FindAll extends Find
{

    /**
     * FindAll command constructor.
     *
     * @ignore
     * @param FileMaker_Implementation $fm FileMaker_Implementation object the 
     *        command was created by.
     * @param string $layout Layout to find all records in.
     */
    public function __construct($fm, $layout)
    {
        parent::__construct($fm, $layout);
    }
    
    
    public function execute() {
        $params = $this->_getCommandParams();
        $params['-findall'] = true;
        $this->_setSortParams($params);
        $this->_setRangeParams($params);
        $result = $this->_fm->_execute($params);
        if (FileMaker::isError($result)) {
            return $result;
        }
        return $this->_getResult($result);
    }

}
