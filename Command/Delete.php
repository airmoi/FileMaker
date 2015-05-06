<?php
namespace airmoi\FileMaker\Command;
use airmoi\FileMaker\FileMaker;
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
 * Command class that deletes a single record.
 * Create this command with {@link FileMaker::newDeleteCommand()}.
 *
 * @package FileMaker
 */
class Delete extends Command
{
    /**
     * Implementation
     *
     * @var FileMaker
     * @access private
     */
    private $_fm;
    
    private $_recordId;

    /**
     * Delete command constructor.
     *
     * @ignore
     * @param FileMaker_Implementation $fm FileMaker_Implementation object the 
     *        command was created by.
     * @param string $layout Layout to delete record from.
     * @param string $recordId ID of the record to delete.
     */
    public function __construct($fm, $layout, $recordId)
    {
        parent::__construct($fm, $layout);
        $this->_recordId = $recordId;
    }
    
    function execute() {
        if (empty($this->_recordId)) {
            $error = new FileMaker_Error($this->_fm, 'Delete commands require a record id.');
            return $error;
        }
        $params = $this->_getCommandParams();
        $params['-delete'] = true;
        $params['-recid'] = $this->_recordId;
        $result = $this->_fm->execute($params);
        if (FileMaker::isError($result)) {
            return $result;
        }
        return $this->_getResult($result);
    }
}
