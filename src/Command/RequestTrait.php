<?php
/**
 * Created by PhpStorm.
 * User: romain
 * Date: 08/12/2016
 * Time: 18:28
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
        if ($this->fm->useDateFormatInRequests && ($fieldType == "date" || $fieldType == "datetime")) {
            $value = DateFormat::convertSearchCriteria($value);
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
