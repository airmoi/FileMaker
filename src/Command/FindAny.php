<?php
/**
 * @copyright Copyright (c) 2016 by 1-more-thing (http://1-more-thing.com) All rights reserved.
 * @license BSD
 */
namespace airmoi\FileMaker\Command;

use airmoi\FileMaker\Object\Result;

/**
 * Command class that finds one random record.
 * Create this command with {@link FileMaker::newFindAnyCommand()}.
 *
 * @package FileMaker
 */
class FindAny extends Find
{
    /**
     *
     * @return Result
     */
    public function execute()
    {
        $params             = $this->getCommandParams();
        $params['-findany'] = true;
        return $this->getResult($this->fm->execute($params));
    }
}
