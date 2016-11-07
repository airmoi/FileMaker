<?php
/**
 * @copyright Copyright (c) 2016 by 1-more-thing (http://1-more-thing.com) All rights reserved.
 * @licence BSD
 */
namespace airmoi\FileMaker\Command;

use airmoi\FileMaker\FileMaker;
use airmoi\FileMaker\FileMakerException;

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
