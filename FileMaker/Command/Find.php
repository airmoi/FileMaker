<?php
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
 * @ignore Include parent and delegate classes.
 */
require_once dirname(__FILE__) . '/../Command.php';
require_once dirname(__FILE__) . '/../Implementation/Command/FindImpl.php';


/**
 * Command class that finds records using the specified criteria.
 * Create this command with {@link FileMaker::newFindCommand()}.
 *
 * @package FileMaker
 */
class FileMaker_Command_Find extends FileMaker_Command
{
    /**
     * Implementation
     *
     * @var FileMaker_Command_Find_Implementation
     * @access private
     */
    var $_impl;

    /**
     * Find command constructor.
     *
     * @ignore
     * @param FileMaker_Implementation $fm FileMaker_Implementation object the 
     *        command was created by.
     * @param string $layout Layout to find records in.
     */
    function FileMaker_Command_Find($fm, $layout)
    {
        $this->_impl = new FileMaker_Command_Find_Implementation($fm, $layout);
    }

    /**
     * Adds a criterion to this Find command.
     *
     * @param string $fieldname Name of the field being tested.
     * @param string $testvalue Value of field to test against.
     */
    function addFindCriterion($fieldname, $testvalue)
    {
        $this->_impl->addFindCriterion($fieldname, $testvalue);
    }

    /**
     * Clears all existing criteria from this Find command.
     */
    function clearFindCriteria()
    {
        $this->_impl->clearFindCriteria();
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
     *        FILEMAKER_SORT_ASCEND constant, the FILEMAKER_SORT_DESCEND 
     *        constant, or the name of a value list specified as a string.
     */
    function addSortRule($fieldname, $precedence, $order = null)
    {
        $this->_impl->addSortRule($fieldname, $precedence, $order);
    }

    /**
     * Clears all existing sorting rules from this Find command.
     */
    function clearSortRules()
    {
        $this->_impl->clearSortRules();
    }

    /**
     * Specifies how the find criteria in this Find command are combined 
     * as either a logical AND or OR search. 
     *
     * If not specified, the default is a logical AND.
     *
     * @param integer $operator Specify the FILEMAKER_FIND_AND or 
     *        FILEMAKER_FIND_OR constant.
     */
    function setLogicalOperator($operator)
    {
        $this->_impl->setLogicalOperator($operator);
    }

    /**
     * Sets a range to request only part of the result set.
     *
     * @param integer $skip Number of records to skip past. Default is 0.
     * @param integer $max Maximum number of records to return. 
     *        Default is all.
     */
    function setRange($skip = 0, $max = null)
    {
        $this->_impl->setRange($skip, $max);
    }

    /**
     * Returns the current range settings.
     *
     * @return array Associative array with two keys: 'skip' for
     * the current skip setting, and 'max' for the current maximum
     * number of records. If either key does not have a value, the 
     * returned value for that key is NULL.
     */
    function getRange()
    {
        return $this->_impl->getRange();
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
    function setRelatedSetsFilters($relatedsetsfilter, $relatedsetsmax = null)
    {
    	return $this->_impl->setRelatedSetsFilters($relatedsetsfilter, $relatedsetsmax);
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
    function getRelatedSetsFilters()
    {
    	return $this->_impl->getRelatedSetsFilters();
    }

}
