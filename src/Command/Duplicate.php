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
 * Command class that duplicates a single record.
 * Create this command with {@link FileMaker::newDuplicateCommand()}.
 *
 * @package FileMaker
 */
class Duplicate extends Command
{
    public $recordId;
    /**
     * Duplicate command constructor.
     *
     * @ignore
     * @param FileMaker $fm FileMaker object the command was created by.
     * @param string $layout Layout the record to duplicate is in.
     * @param string $recordId ID of the record to duplicate.
     */
    public function __construct(FileMaker $fm, $layout, $recordId)
    {
        parent::__construct($fm, $layout);
        $this->recordId = $recordId;
    }

    /**
     * Return a Result object with the duplicated record
     * use Result->getFirstRecord() to get the record
     *
     * @return Result|FileMakerException
     * @throws FileMakerException
     */
    public function execute()
    {
        if (empty($this->recordId)) {
            return $this->fm->returnOrThrowException('Duplicate commands require a record id.');
        }
        $params = $this->getCommandParams();
        $params['-dup'] = true;
        $params['-recid'] = $this->recordId;
        $result = $this->fm->execute($params);
        return $this->getResult($result);
    }

    protected function getResult($response)
    {
        if ($this->fm->engine == 'cwp') {
            $result = parent::getResult($response);
        } else {
            $parser      = new DataApiResult($this->fm);
            $parseResult = $parser->parse($response);
            if (FileMaker::isError($parseResult)) {
                return $parseResult;
            }
            $result = new Result($this->fm);
            $result->records[] = $this->fm->getRecordById($this->layout, $parser->parsedResult['recordId']);
        }
        return $result;
    }
}
