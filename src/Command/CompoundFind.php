<?php
/**
 * @copyright Copyright (c) 2016 by 1-more-thing (http://1-more-thing.com) All rights reserved.
 * @license BSD
 */
namespace airmoi\FileMaker\Command;

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
    private $sortFields = [];
    private $sortOrders = [];
    private $skip;
    private $max;
    private $relatedsetsfilter;
    private $relatedsetsmax;

    /**
     *
     * @var FindRequest[]
     */
    private $requests = [];

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
        $this->requests[$precedence] = $findrequest;
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
        $this->sortFields[$precedence] = $fieldname;
        if ($order !== null) {
            $this->sortOrders[$precedence] = $order;
        }
    }

    /**
     * Clears all existing sorting rules from this Compound Find command.
     */
    public function clearSortRules()
    {
        $this->sortFields = [];
        $this->sortOrders = [];
    }

    /**
     *
     * @return \airmoi\FileMaker\Object\Result|\airmoi\FileMaker\FileMakerException
     * @throws \airmoi\FileMaker\FileMakerException
     */
    public function execute()
    {
        $query = null;
        $requestCount = 1;
        $totalCritCount = 1;

        $params = $this->getCommandParams();
        $this->setSortParams($params);
        $this->setRangeParams($params);
        $this->setRelatedSetsFiltersParams($params);

        ksort($this->requests);
        $totalRequestCount = count($this->requests);
        
        foreach ($this->requests as $precedence => $request) {
            $findCriterias = $request->findCriteria;
            $critCount = count($findCriterias);

            //Handle first request omit flag
            if ($request->omit) {
                $query .= '!';
            }

            $query .= '(';

            $i = 0;
            foreach ($findCriterias as $fieldname => $testvalue) {
                $params['-q' . $totalCritCount] = $fieldname;
                $params['-q' . $totalCritCount . '.' . "value"] = $testvalue;
                $query = $query . 'q' . $totalCritCount;
                $totalCritCount++;
                $i++;

                if ($i < $critCount) {
                    $query .= ',';
                }
            }
            $query .= ')';
            $requestCount++;
            if ($requestCount <= $totalRequestCount) {
                $query .= ';';
            }
        }
        $params['-query'] = $query;
        $params['-findquery'] = true;
        $result = $this->fm->execute($params);
        return $this->getResult($result);
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
        $this->skip = $skip;
        $this->max = $max;
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
        return [
            'skip' => $this->skip,
            'max'  => $this->max
        ];
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
        $this->relatedsetsfilter = $relatedsetsfilter;
        $this->relatedsetsmax = $relatedsetsmax;
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
        return [
            'relatedsetsfilter' => $this->relatedsetsfilter,
            'relatedsetsmax' => $this->relatedsetsmax
        ];
    }

    /**
     * Build relatedSets Filter params
     * @param $params
     */
    public function setRelatedSetsFiltersParams(&$params)
    {
        if ($this->relatedsetsfilter) {
            $params['-relatedsets.filter'] = $this->relatedsetsfilter;
        }
        if ($this->relatedsetsmax) {
            $params['-relatedsets.max'] = $this->relatedsetsmax;
        }
    }

    /**
     * Build sort params
     * @param $params
     */
    public function setSortParams(&$params)
    {
        foreach ($this->sortFields as $precedence => $fieldname) {
            $params['-sortfield.' . $precedence] = $fieldname;
        }
        foreach ($this->sortOrders as $precedence => $order) {
            $params['-sortorder.' . $precedence] = $order;
        }
    }

    /**
     * Build range params
     * @param $params
     */
    public function setRangeParams(&$params)
    {
        if ($this->skip) {
            $params['-skip'] = $this->skip;
        }
        if ($this->max) {
            $params['-max'] = $this->max;
        }
    }
}
