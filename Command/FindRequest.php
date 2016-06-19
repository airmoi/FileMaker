<?php
namespace airmoi\FileMaker\Command;

/**
 * FileMaker API for PHP
 *
 * @package FileMaker
 *
 * Copyright © 2005-2007, FileMaker, Inc. All rights reserved.
 * NOTE: Use of this source code is subject to the terms of the FileMaker
 * Software License which accompanies the code. Your use of this source code
 * signifies your agreement to such license terms and conditions. Except as
 * expressly granted in the Software License, no other copyright, patent, or
 * other intellectual property license or right is granted, either expressly or
 * by implication, by FileMaker.
 */



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
