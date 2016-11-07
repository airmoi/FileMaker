<?php
/**
 * @copyright Copyright (c) 2016 by 1-more-thing (http://1-more-thing.com) All rights reserved.
 * @licence BSD
 */
namespace airmoi\FileMaker\Command;

use airmoi\FileMaker\Object\Result;

/**
 * Command class that finds all records from a layout.
 * Create this command with {@link FileMaker::newFindAllCommand()}.
 *
 * @package FileMaker
 */
class FindAll extends Find
{
    /**
     *
     * @return Result|\airmoi\FileMaker\FileMakerException
     * @throws \airmoi\FileMaker\FileMakerException
     */
    public function execute() {
        $params = $this->_getCommandParams();
        $params['-findall'] = true;
        $this->_setSortParams($params);
        $this->_setRangeParams($params);
        $result = $this->fm->execute($params);
        return $this->_getResult($result);
    }

}
