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
 * Command class that performs multiple find requests, also known as a compound
 * find set.
 * Requests are executed in the order specified in the add() method. The found
 * set includes the results of the entire compound find request.
 * Create this command with {@link FileMaker::newCompoundFindCommand()}.
 *
 * @package FileMaker
 */
class CompoundFind extends Command
{
    private $_sortFields = array();
    private $_sortOrders = array();
    private $_skip;
    private $_max;
    private $_relatedsetsfilter;
    private $_relatedsetsmax;

    /**
     *
     * @var FindRequest[]
     */
    private $_requests = array();

     /**
     * Adds a Find Request object to this Compound Find command.
     *
     * @param int $precedence Priority in which the find requests are added to
     *        this compound find set.
     * @param FindRequest $findrequest {@link FindRequest} object
     *        to add to this compound find set.
     */
    public function add($precedence, FindRequest $findrequest)
    {
        $this->_requests[$precedence] = $findrequest;
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
    public function addSortRule($fieldname, $precedence, $order = null)
    {
        $this->_sortFields[$precedence] = $fieldname;
        if ($order !== null) {
            $this->_sortOrders[$precedence] = $order;
        }
    }

    /**
     * Clears all existing sorting rules from this Compound Find command.
     */
    public function clearSortRules()
    {
        $this->_sortFields = array();
        $this->_sortOrders = array();
    }

    /**
     *
     * @return \airmoi\FileMaker\Object\Result
     * @throws \airmoi\FileMaker\FileMakerException
     */
    public function execute() {
        $query = null;

        $critCount = 0;
        $totalRequestCount = 0;
        $requestCount = 1;
        $totalCritCount = 1;
        $params = $this->_getCommandParams();
        $this->_setSortParams($params);
        $this->_setRangeParams($params);
        $this->_setRelatedSetsFilters($params);
        ksort($this->_requests);
        $totalRequestCount = count($this->_requests);
        foreach ($this->_requests as $precedence => $request) {
            $findCriterias = $request->findCriteria;
            $critCount = count($findCriterias);

            $query = $query . '(';

            $i = 0;
            foreach ($findCriterias as $fieldname => $testvalue) {
                $params['-q' . $totalCritCount] = $fieldname;
                $params['-q' . $totalCritCount . '.' . "value"] = $testvalue;
                $query = $query . 'q' . $totalCritCount;
                $totalCritCount++;
                $i++;

                if ($i < $critCount) {
                    $query = $query . ',';
                }
            }
            $query = $query . ")";
            $requestCount++;
            if ($requestCount <= $totalRequestCount) {
                $nextRequest = $this->_requests[$precedence + 1];
                if ($nextRequest->omit == true) {
                    $query = $query . ';!';
                } else {
                    $query = $query . ';';
                }
            }
        }
        $params['-query'] = $query;
        $params['-findquery'] = true;
        $result = $this->fm->execute($params);
        return $this->_getResult($result);
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
     * For more information, see the description for the
     * {@link Find::setRelatedSetsFilters()} method.
     *
     * @param string $relatedsetsfilter Specify either 'layout' or 'none' to
     *        control filtering.
     * @param string $relatedsetsmax Maximum number of portal records
     *        to return.
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

    public function _setRelatedSetsFilters(&$params) {
        if ($this->_relatedsetsfilter) {
            $params['-relatedsets.filter'] = $this->_relatedsetsfilter;
        }
        if ($this->_relatedsetsmax) {
            $params['-relatedsets.max'] = $this->_relatedsetsmax;
        }
    }

    public function _setSortParams(&$params) {
        foreach ($this->_sortFields as $precedence => $fieldname) {
            $params['-sortfield.' . $precedence] = $fieldname;
        }
        foreach ($this->_sortOrders as $precedence => $order) {
            $params['-sortorder.' . $precedence] = $order;
        }
    }

    public function _setRangeParams(&$params) {
        if ($this->_skip) {
            $params['-skip'] = $this->_skip;
        }
        if ($this->_max) {
            $params['-max'] = $this->_max;
        }
    }
}
