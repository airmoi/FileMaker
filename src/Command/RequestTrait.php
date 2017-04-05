<?php
/**
 * @copyright Copyright (c) 2016 by 1-more-thing (http://1-more-thing.com) All rights reserved.
 * @license BSD
 */

namespace airmoi\FileMaker\Command;

use airmoi\FileMaker\Helpers\DateFormat;

trait RequestTrait
{
    public $findCriteria = [];

    /**
     * Adds a criterion to this Find command.
     *
     * @param string $fieldName Name of the field being tested.
     * @param string $value Value of field to test against.
     *
     * @return self
     */
    public function addFindCriterion($fieldName, $value)
    {
        $fieldType = $this->getFieldResult($fieldName);
        if ($this->fm->useDateFormatInRequests
            && $this->fm->dateFormat !== null
            && ($fieldType == "date" || $fieldType == "datetime")
        ) {
            $value = DateFormat::convertSearchCriteria($value, $this->fm->dateFormat, 'm/d/Y');
        }
        $this->findCriteria[$fieldName] = $value;
        return $this;
    }

    /**
     * Clears all existing criteria from this find request.
     *
     * @return self
     */
    public function clearFindCriteria()
    {
        $this->findCriteria = [];
        return $this;
    }

    /**
     *
     * @return bool true if the request as no criterion set
     */
    public function isEmpty()
    {
        return empty($this->findCriteria);
    }
}
