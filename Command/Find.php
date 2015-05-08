<?php
namespace airmoi\FileMaker\Command;
use airmoi\FileMaker\FileMaker;
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
 * Command class that finds records using the specified criteria.
 * Create this command with {@link FileMaker::newFindCommand()}.
 *
 * @package FileMaker
 */
class Find extends Command
{
    
    private $_findCriteria = array();
    private $_sortRules = array();
    private $_sortOrders = array();
    private $_operator;
    private $_skip;
    private $_max;
    private $_relatedsetsfilter;
    private $_relatedsetsmax;

    /**
     * Find command constructor.
     *
     * @ignore
     * @param FileMaker_Implementation $fm FileMaker_Implementation object the 
     *        command was created by.
     * @param string $layout Layout to find records in.
     */
    public function __construct($fm, $layout)
    {
        parent::__construct($fm, $layout);
    }

    /**
     * Adds a criterion to this Find command.
     *
     * @param string $fieldname Name of the field being tested.
     * @param string $testvalue Value of field to test against.
     */
    public function addFindCriterion($fieldname, $testvalue)
    {
        $this->_findCriteria[$fieldname] = $testvalue;
    }

    /**
     * Clears all existing criteria from this Find command.
     */
    public function clearFindCriteria()
    {
        $this->_findCriteria = [];
    }

    /**
     * Adds a sorting rule to this Find command.
     *
     * @param string $fieldname Name of the field to sort by.
     * @param integer $precedence Integer from 1 to 9, inclusive. A value 
     *        of 1 sorts records based on this sorting rule first, a value of 
     *        2 sorts records based on this sorting rule only when two or more 
     *        records have the same value after the first sorting rule is 
     *        applied, and so on.
     * @param mixed $order Direction of the sort. Specify the 
     *        FileMaker::SORT_ASCEND constant, the FileMaker::SORT_DESCEND 
     *        constant, or the name of a value list specified as a string.
     */
    public function addSortRule($fieldname, $precedence, $order = null)
    {
         $this->_sortRules[$precedence] = $fieldname;
        if ($order !== null) {
            $this->_sortOrders[$precedence] = $order;
        }
    }

    /**
     * Clears all existing sorting rules from this Find command.
     */
    public function clearSortRules()
    {
         $this->_sortRules = array();
        $this->_sortOrders = array();
    }
    
    public function execute() {
        $params = $this->_getCommandParams();
        $this->_setSortParams($params);
        $this->_setRangeParams($params);
        $this->_setRelatedSetsFilters($params);
        if (count($this->_findCriteria) || $this->recordId) {
            $params['-find'] = true;
        } else {
            $params['-findall'] = true;
        }
        if ($this->recordId) {
            $params['-recid'] = $this->recordId;
        }
        if ($this->_operator) {
            $params['-lop'] = $this->_operator;
        }
        foreach ($this->_findCriteria as $field => $value) {
            $params[$field] = $value;
        }
        $result = $this->fm->execute($params);
        if (FileMaker::isError($result)) {
            return $result;
        }
        return $this->_getResult($result);
    }

    /**
     * Specifies how the find criteria in this Find command are combined 
     * as either a logical AND or OR search. 
     *
     * If not specified, the default is a logical AND.
     *
     * @param integer $operator Specify the FileMaker::FIND_AND or 
     *        FileMaker::FIND_OR constant.
     */
    public function setLogicalOperator($operator)
    {
         switch ($operator) {
            case FileMaker::FIND_AND:
            case FileMaker::FIND_OR:
                $this->_operator = $operator;
                break;
        }
    }

    /**
     * Sets a range to request only part of the result set.
     *
     * @param integer $skip Number of records to skip past. Default is 0.
     * @param integer $max Maximum number of records to return. 
     *        Default is all.
     */
    public function setRange($skip = 0, $max = null)
    {
         $this->_skip = $skip;
        $this->_max = $max;
    }

    /**
     * Returns the current range settings.
     *
     * @return array Associative array with two keys: 'skip' for
     * the current skip setting, and 'max' for the current maximum
     * number of records. If either key does not have a value, the 
     * returned value for that key is NULL.
     */
    public function getRange()
    {
        return array('skip' => $this->_skip,
            'max' => $this->_max);
    }
    
    /**
     * Sets a filter to restrict the number of related records to return from 
     * a portal. 
     *
     * The filter limits the number of related records returned by respecting 
     * the settings specified in the FileMaker Pro Portal Setup dialog box. 
     *
     * @param string $relatedsetsfilter Specify one of these values to  
     *        control filtering: 
     *        - 'layout': Apply the settings specified in the FileMaker Pro 
     *                    Portal Setup dialog box. The records are sorted based 
     *                    on the sort  defined in the Portal Setup dialog box, 
     *                    with the record set filtered to start with the 
     *                    specified "Initial row."
     *        - 'none': Return all related records in the portal without 
     *                  filtering or presorting them.
     * 
     * @param string $relatedsetsmax If the "Show vertical scroll bar" setting 
     *        is enabled in the Portal Setup dialog box, specify one of these 
     *        values:
     *        - an integer value: Return this maximum number of related records 
     *                            after the initial record.
     *        - 'all': Return all of the related records in the portal.
     *                 If "Show vertical scroll bar" is disabled, the Portal 
     *                 Setup dialog box's "Number of rows" setting determines 
     *                 the maximum number of related records to return. 
     */
    public function setRelatedSetsFilters($relatedsetsfilter, $relatedsetsmax = null)
    {
    	$this->_relatedsetsfilter = $relatedsetsfilter;
        $this->_relatedsetsmax = $relatedsetsmax;
    }
    
    /**
     * Returns the current settings for the related records filter and  
     * the maximum number of related records to return.
     *
     * @return array Associative array with two keys: 'relatedsetsfilter' for 
     * the portal filter setting, and 'relatedsetsmax' for the maximum 
     * number of records. If either key does not have a value, the returned 
     * for that key is NULL.
     */
    public function getRelatedSetsFilters()
    {
    	return array('relatedsetsfilter' => $this->_relatedsetsfilter,
            'relatedsetsmax' => $this->_relatedsetsmax);
    }
    
    protected function _setRelatedSetsFilters(&$params) {
        if ($this->_relatedsetsfilter) {
            $params['-relatedsets.filter'] = $this->_relatedsetsfilter;
        }
        if ($this->_relatedsetsmax) {
            $params['-relatedsets.max'] = $this->_relatedsetsmax;
        }
    }

    protected function _setSortParams(&$params) {
        foreach ($this->_sortRules as $precedence => $fieldname) {
            $params['-sortfield.' . $precedence] = $fieldname;
        }
        foreach ($this->_sortOrders as $precedence => $order) {
            $params['-sortorder.' . $precedence] = $order;
        }
    }

    protected function _setRangeParams(&$params) {
        if ($this->_skip) {
            $params['-skip'] = $this->_skip;
        }
        if ($this->_max) {
            $params['-max'] = $this->_max;
        }
    }

}
