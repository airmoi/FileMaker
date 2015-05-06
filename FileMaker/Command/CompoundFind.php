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
require_once dirname(__FILE__) . '/../Implementation/Command/CompoundFindImpl.php';


/**
 * Command class that performs multiple find requests, also known as a compound 
 * find set. 
 * Requests are executed in the order specified in the add() method. The found 
 * set includes the results of the entire compound find request.
 * Create this command with {@link FileMaker::newCompoundFindCommand()}.
 *
 * @package FileMaker
 */
class FileMaker_Command_CompoundFind extends FileMaker_Command
{
    /**
     * Implementation
     *
     * @var FileMaker_Command_CompoundFind_Implementation
     * @access private
     */
    var $_impl;

    /**
     * Compound find set constructor.
     *
     * @ignore
     * @param FileMaker_Implementation $fm FileMaker_Implementation object the 
     *        request was created by.
     * @param string $layout Layout to find records in.
     */
    function FileMaker_Command_CompoundFind($fm, $layout)
    {
        $this->_impl = new FileMaker_Command_CompoundFind_Implementation($fm, $layout);
    }
  
     /**
     * Adds a Find Request object to this Compound Find command.
     *
     * @param int $precedence Priority in which the find requests are added to 
     *        this compound find set.
     * @param findrequest $findrequest {@link FileMaker_FindRequest} object 
     *        to add to this compound find set. 
     */
    function add($precedence, $findrequest)
    {
        $this->_impl->add($precedence, $findrequest);
    }
    
     /**
     * Adds a sorting rule to this Compound Find command.
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
     * Clears all existing sorting rules from this Compound Find command.
     */
    function clearSortRules()
    {
        $this->_impl->clearSortRules();
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
     * For more information, see the description for the 
     * {@link FileMaker_Command_Find::setRelatedSetsFilters()} method.
     *
     * @param string $relatedsetsfilter Specify either 'layout' or 'none' to 
     *        control filtering.  
     * @param string $relatedsetsmax Maximum number of portal records 
     *        to return.
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
