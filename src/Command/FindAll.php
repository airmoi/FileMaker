<?php
/**
 * @copyright Copyright (c) 2016 by 1-more-thing (http://1-more-thing.com) All rights reserved.
 * @license BSD
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
    public function execute($result = null)
    {
        $params             = $this->getCommandParams();
        $params['-findall'] = true;
        $this->setSortParams($params);
        $this->setRangeParams($params);

        $result = $this->getResult($this->fm->execute($params), $result);

        //Handle auto pagination
        if ($this->max
            || $result->getFoundSetCount() == 0
            || $result->getFoundSetCount() == $result->getFetchCount()
        ) {
            return $result;
        }

        $pages = $result->getFoundSetCount()/100;
        for ($i = 1 ; $i < $pages; $i++) {
            $this->setRange(($i-1)*100, 100);
            $pageResult = $this->execute($result);
        }
        $result->fetchCount = $result->getFoundSetCount();
        return $result;
    }
}
