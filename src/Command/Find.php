<?php
/**
 * @copyright Copyright (c) 2016 by 1-more-thing (http://1-more-thing.com) All rights reserved.
 * @license BSD
 */
namespace airmoi\FileMaker\Command;

use airmoi\FileMaker\FileMaker;
use airmoi\FileMaker\FileMakerException;
use airmoi\FileMaker\Helpers\DateFormat;

/**
 * Command class that finds records using the specified criteria.
 * Create this command with {@link FileMaker::newFindCommand()}.
 *
 * @package FileMaker
 */
class Find extends Command
{

    protected $findCriteria = [];
    protected $sortRules = [];
    protected $sortOrders = [];
    protected $operator;
    protected $skip;
    protected $max;
    protected $relatedsetsfilter;
    protected $relatedsetsmax;

    /**
     * Adds a criterion to this Find command.
     *
     * @param string $fieldname Name of the field being tested.
     * @param string $value Value of field to test against.
     *
     * @return self
     */
    public function addFindCriterion($fieldname, $value)
    {
        if ($this->getFieldResult($fieldname) == "date" || $this->getFieldResult($fieldname) == "datetime") {
            $value = DateFormat::convertSearchCriteria($value);
        }
        $this->findCriteria[$fieldname] = $value;
        return $this;
    }

    /**
     * Clears all existing criteria from this Find command.
     *
     * @return self
     */
    public function clearFindCriteria()
    {
        $this->findCriteria = [];
        return $this;
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
     *
     * @return self
     */
    public function addSortRule($fieldname, $precedence, $order = null)
    {
         $this->sortRules[$precedence] = $fieldname;
        if ($order !== null) {
            $this->sortOrders[$precedence] = $order;
        }
        return $this;
    }

    /**
     * Clears all existing sorting rules from this Find command.
     *
     * @return self
     */
    public function clearSortRules()
    {
        $this->sortRules = [];
        $this->sortOrders = [];
        return $this;
    }

    /**
     * Execute the command
     * @return \airmoi\FileMaker\FileMakerException|\airmoi\FileMaker\Object\Result|string
     * @throws \airmoi\FileMaker\FileMakerException
     */
    public function execute()
    {
        $params = $this->getCommandParams();
        $this->setSortParams($params);
        $this->setRangeParams($params);
        $this->setRelatedSetsFiltersParams($params);
        if (count($this->findCriteria) || $this->recordId) {
            $params['-find'] = true;
        } else {
            $params['-findall'] = true;
        }
        if ($this->recordId) {
            $params['-recid'] = $this->recordId;
        }
        if ($this->operator) {
            $params['-lop'] = $this->operator;
        }
        foreach ($this->findCriteria as $field => $value) {
            $params[$field] = $value;
        }
        $result = $this->fm->execute($params);
        if (FileMaker::isError($result)) {
            return $result;
        }
        return $this->getResult($result);
    }

    /**
     * Specifies how the find criteria in this Find command are combined
     * as either a logical AND or OR search.
     *
     * If not specified, the default is a logical AND.
     *
     * @param integer $operator Specify the FileMaker::FIND_AND or
     *        FileMaker::FIND_OR constant.
     *
     * @return self
     */
    public function setLogicalOperator($operator)
    {
        switch ($operator) {
            case FileMaker::FIND_AND:
            case FileMaker::FIND_OR:
                $this->operator = $operator;
                break;
        }
        return $this;
    }

    /**
     * Sets a range to request only part of the result set.
     *
     * @param integer $skip Number of records to skip past. Default is 0.
     * @param integer $max Maximum number of records to return.
     *        Default is all.
     *
     * @return self
     */
    public function setRange($skip = 0, $max = null)
    {
        $this->skip = $skip;
        $this->max = $max;
        return $this;
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
            'max' => $this->max
        ];
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
     *
     * @return self
     */
    public function setRelatedSetsFilters($relatedsetsfilter, $relatedsetsmax = null)
    {
        $this->relatedsetsfilter = $relatedsetsfilter;
        $this->relatedsetsmax = $relatedsetsmax;
        return $this;
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
     * Set relatedSet Filters params
     * @param array $params
     */
    protected function setRelatedSetsFiltersParams(&$params)
    {
        if ($this->relatedsetsfilter) {
            $params['-relatedsets.filter'] = $this->relatedsetsfilter;
        }
        if ($this->relatedsetsmax) {
            $params['-relatedsets.max'] = $this->relatedsetsmax;
        }
    }

    /**
     * Set sort Params
     * @param array $params
     */
    protected function setSortParams(&$params)
    {
        foreach ($this->sortRules as $precedence => $fieldname) {
            $params['-sortfield.' . $precedence] = $fieldname;
        }
        foreach ($this->sortOrders as $precedence => $order) {
            $params['-sortorder.' . $precedence] = $order;
        }
    }

    /**
     * Set Range params
     * @param array $params
     */
    protected function setRangeParams(&$params)
    {
        if ($this->skip) {
            $params['-skip'] = $this->skip;
        }
        if ($this->max) {
            $params['-max'] = $this->max;
        }
    }

    /**
     * @return \airmoi\FileMaker\FileMakerException|\airmoi\FileMaker\Object\Layout
     */
    private function getLayout()
    {
        return $this->fm->getLayout($this->layout);
    }

    /**
     * Get the field "type" (date/text/number...)
     * @param $fieldName
     *
     * @return null|Field|FileMakerException Field object, if successful.
     * @throws FileMakerException
     */
    private function getFieldResult($fieldName)
    {
        try {
            $field = $this->getLayout()->getField($fieldName);
            if(FileMaker::isError($field)) {
                throw $field;
            }
        } catch ( FileMakerException $e ){
            return null;
        }
        return $field->result;
    }
}
