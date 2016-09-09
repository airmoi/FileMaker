<?php
/**
 * @copyright Copyright (c) 2016 by 1-more-thing (http://1-more-thing.com) All rights reserved.
 * @licence BSD
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
    
    public $findCriteria = array();
    public $omit;

    /**
     * Find request constructor.
     *
     * @ignore
     * @param \airmoi\FileMaker\FileMaker $fm FileMaker object the request was created by.
     * @param string $layout Layout to find records in.
     */
    public function __construct($fm, $layout)
    {
        $this->omit = false;
    }

    /**
     * Sets whether this request is an omit request.
     * 
     * An omit request removes the matching records from the final result set.
     *
     * @param boolean $value TRUE if this is an omit request. Otherwise, FALSE.
     */
    public function setOmit($value)
    {
        $this->omit = $value;
    }

    /**
     * Adds a criterion to this find request.
     *
     * @param string $fieldname Name of the field being tested.
     * @param string $testvalue Value of the field to test against.
     */
    public function addFindCriterion($fieldname, $testvalue)
    {
        $this->findCriteria[$fieldname] = $testvalue;
    }
    
    /**
     * Clears all existing criteria from this find request.
     */
    public function clearFindCriteria()
    {
        $this->findCriteria = array();
    }
    
    /**
     * 
     * @return bool true if the request as no criterion set
     */
    public function isEmpty() {
        return sizeof($this->findCriteria) === 0;
    }

}
