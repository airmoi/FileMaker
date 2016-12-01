<?php
/**
 * @copyright Copyright (c) 2016 by 1-more-thing (http://1-more-thing.com) All rights reserved.
 * @license BSD
 */
namespace airmoi\FileMaker\Command;

/**
 * Find Request class. Contains all the information about a single find request
 * for a Compound Find command.
 * Create this command with {@link FileMaker::newFindRequest()}.
 *
 * @package FileMaker
 */
class FindRequest
{

    public $findCriteria = [];
    public $omit;

    /**
     * Find request constructor.
     *
     * @ignore
     */
    public function __construct()
    {
        $this->omit = false;
    }

    /**
     * Sets whether this request is an omit request.
     *
     * An omit request removes the matching records from the final result set.
     *
     * @param boolean $value TRUE if this is an omit request. Otherwise, FALSE.
     *
     * @return self
     */
    public function setOmit($value)
    {
        $this->omit = $value;
        return $this;
    }

    /**
     * Adds a criterion to this find request.
     *
     * @param string $fieldname Name of the field being tested.
     * @param string $testvalue Value of the field to test against.
     *
     * @return self
     */
    public function addFindCriterion($fieldname, $testvalue)
    {
        $this->findCriteria[$fieldname] = $testvalue;
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
