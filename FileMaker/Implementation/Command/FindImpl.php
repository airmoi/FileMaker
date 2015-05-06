<?php

require_once dirname(__FILE__) . '/../CommandImpl.php';

class FileMaker_Command_Find_Implementation extends FileMaker_Command_Implementation {

    private $_findCriteria = array();
    private $_sortRules = array();
    private $_sortOrders = array();
    private $_operator;
    private $_skip;
    private $_max;
    private $_relatedsetsfilter;
    private $_relatedsetsmax;

    function FileMaker_Command_Find_Implementation($fm, $layout) {
        FileMaker_Command_Implementation::FileMaker_Command_Implementation($fm, $layout);
    }

    function execute() {
        $params = $this->_getCommandParams();
        $this->_setSortParams($params);
        $this->_setRangeParams($params);
        $this->_setRelatedSetsFilters($params);
        if (count($this->_findCriteria) || $this->_recordId) {
            $params['-find'] = true;
        } else {
            $params['-findall'] = true;
        }
        if ($this->_recordId) {
            $params['-recid'] = $this->_recordId;
        }
        if ($this->_operator) {
            $params['-lop'] = $this->_operator;
        }
        foreach ($this->_findCriteria as $field => $value) {
            $params[$field] = $value;
        }
        $result = $this->_fm->_execute($params);
        if (FileMaker::isError($result)) {
            return $result;
        }
        return $this->_getResult($result);
    }

    function addFindCriterion($Vd1148ee8, $Ve9de89b0) {
        $this->_findCriteria[$Vd1148ee8] = $Ve9de89b0;
    }

    function clearFindCriteria() {
        $this->_findCriteria = array();
    }

    function addSortRule($fieldname, $precedence, $order = null) {
        $this->_sortRules[$precedence] = $fieldname;
        if ($order !== null) {
            $this->_sortOrders[$precedence] = $order;
        }
    }

    function clearSortRules() {
        $this->_sortRules = array();
        $this->_sortOrders = array();
    }

    function setLogicalOperator($operator) {
        switch ($operator) {
            case FILEMAKER_FIND_AND:
            case FILEMAKER_FIND_OR:
                $this->_operator = $operator;
                break;
        }
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
        foreach ($this->_sortRules as $precedence => $fieldname) {
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
