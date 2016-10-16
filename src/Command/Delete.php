<?php
namespace airmoi\FileMaker\Command;

use airmoi\FileMaker\FileMaker;
use airmoi\FileMaker\FileMakerException;

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
     * Delete command constructor.
     *
     * @ignore
     * @param FileMaker $fm FileMaker object the command was created by.
     * @param string $layout Layout to delete record from.
     * @param string $recordId ID of the record to delete.
     */
    public function __construct(FileMaker $fm, $layout, $recordId)
    {
        parent::__construct($fm, $layout);
        $this->recordId = $recordId;
    }
    
    /**
     * 
     * @return \airmoi\FileMaker\Object\Result|FileMakerException
     * @throws FileMakerException
     */
    public function execute() {
        if (empty($this->recordId)) {
            $error = new FileMakerException($this->fm, 'Delete commands require a record id.');
            if($this->fm->getProperty('errorHandling') == 'default') {
                return $error;
            }
            throw $error;
        }
        $params = $this->_getCommandParams();
        $params['-delete'] = true;
        $params['-recid'] = $this->recordId;
        $result = $this->fm->execute($params);
        return $this->_getResult($result);
    }
}
