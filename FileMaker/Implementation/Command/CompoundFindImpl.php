<?php

require_once dirname(__FILE__) . '/../CommandImpl.php';

class FileMaker_Command_CompoundFind_Implementation extends FileMaker_Command_Implementation {

    private $_findCriteria = array();
    private $_sortFields = array();
    private $_sortOrders = array();
    private $_skip;
    private $_max;
    private $_relatedsetsfilter;
    private $_relatedsetsmax;
    private $_requests = array();

    function FileMaker_Command_CompoundFind_Implementation($fm, $layout) {
        FileMaker_Command_Implementation::FileMaker_Command_Implementation($fm, $layout);
    }

    function &execute() {
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
            $findCriterias = $request->_impl->_findCriteria;
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
                $nextRequest = $this->_requests[$requestCount];
                if ($nextRequest->_impl->_omit == true) {
                    $query = $query . ';!';
                } else {
                    $query = $query . ';';
                }
            }
        }
        $params['-query'] = $query;
        $params['-findquery'] = true;
        $result = $this->_fm->_execute($params);
        if (FileMaker::isError($result)) {
            return $result;
        }
        return $this->_getResult($result);
    }

    function add($precedence, $findrequest) {
        $this->_requests[$precedence] = $findrequest;
    }

    function addSortRule($fieldname, $precedence, $order = null) {
        $this->_sortFields[$precedence] = $fieldname;
        if ($order !== null) {
            $this->_sortOrders[$precedence] = $order;
        }
    }

    function clearSortRules() {
        $this->_sortFields = array();
        $this->_sortOrders = array();
    }

    function setRange($skip = 0, $max = null) {
        $this->_skip = $skip;
        $this->_max = $max;
    }

    function getRange() {
        return array('skip' => $this->_skip,
            'max' => $this->_max);
    }

    function setRelatedSetsFilters($relatedsetsfilter, $relatedsetsmax = null) {
        $this->_relatedsetsfilter = $relatedsetsfilter;
        $this->_relatedsetsmax = $relatedsetsmax;
    }

    function getRelatedSetsFilters() {
        return array('relatedsetsfilter' => $this->_relatedsetsfilter,
            'relatedsetsmax' => $this->_relatedsetsmax);
    }

    function _setRelatedSetsFilters(&$params) {
        if ($this->_relatedsetsfilter) {
            $params['-relatedsets.filter'] = $this->_relatedsetsfilter;
        }
        if ($this->_relatedsetsmax) {
            $params['-relatedsets.max'] = $this->_relatedsetsmax;
        }
    }

    function _setSortParams(&$params) {
        foreach ($this->_sortFields as $precedence => $fieldname) {
            $params['-sortfield.' . $precedence] = $fieldname;
        }
        foreach ($this->_sortOrders as $precedence => $order) {
            $params['-sortorder.' . $precedence] = $order;
        }
    }

    function _setRangeParams(&$params) {
        if ($this->_skip) {
            $params['-skip'] = $this->_skip;
        }
        if ($this->_max) {
            $params['-max'] = $this->_max;
        }
    }

}
