<?php
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
 * @ignore Include parent and delegate classes.
 */
require_once dirname(__FILE__) . '/Find.php';
require_once dirname(__FILE__) . '/../Implementation/Command/FindAllImpl.php';


/**
 * Command class that finds all records from a layout. 
 * Create this command with {@link FileMaker::newFindAllCommand()}.
 *
 * @package FileMaker
 */
class FileMaker_Command_FindAll extends FileMaker_Command_Find
{
    /**
     * Implementation
     *
     * @var FileMaker_Command_FindAll_Implementation
     * @access private
     */
    var $_impl;

    /**
     * FindAll command constructor.
     *
     * @ignore
     * @param FileMaker_Implementation $fm FileMaker_Implementation object the 
     *        command was created by.
     * @param string $layout Layout to find all records in.
     */
    function FileMaker_Command_FindAll($fm, $layout)
    {
        $this->_impl = new FileMaker_Command_FindAll_Implementation($fm, $layout);
    }

}
