<?php
/**
 * @copyright Copyright (c) 2016 by 1-more-thing (http://1-more-thing.com) All rights reserved.
 * @license BSD
 */
namespace airmoi\FileMaker\Command;

use airmoi\FileMaker\FileMaker;
use airmoi\FileMaker\FileMakerException;
use airmoi\FileMaker\Object\Result;
use airmoi\FileMaker\Parser\DataApiResult;

/**
 * Command class that deletes a single record.
 * Create this command with {@link FileMaker::newDeleteCommand()}.
 *
 * @package FileMaker
 */
class Delete extends Command
{

    public $recordId;
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
    public function execute($result = null)
    {
        if (empty($this->recordId)) {
            return $this->fm->returnOrThrowException('Delete commands require a record id.');
        }
        $params = $this->getCommandParams();
        $params['-delete'] = true;
        $params['-recid'] = $this->recordId;
        $result = $this->fm->execute($params);
        return $this->getResult($result);
    }

    protected function getResult($response, $result = null)
    {
        if (!$this->fm->useDataApi) {
            $result = parent::getResult($response);
        } else {
            $parser      = new DataApiResult($this->fm);
            $parseResult = $parser->parse($response);
            if (FileMaker::isError($parseResult)) {
                return $parseResult;
            }
            $result = new Result($this->fm);
            /*$result->records[] = $this->fm->getRecordById($this->layout, $this->recordId);
            $result = new Result($this->fm);*/
        }
        return $result;
    }
}
