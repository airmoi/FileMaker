<?php
namespace airmoi\FileMaker\Implementation;
use airmoi\FileMaker\Error;

class FileMaker_Field_Implementation {

    private $_layout;
    private $_name;
    private $_autoEntered = false;
    private $_global = false;
    private $_maxRepeat = 1;
    private $_validationMask = 0;
    private $_validationRules = array();
    private $_result;
    private $_type;
    private $_valueList = null;
    private $_styleType;
    private $_maxCharacters = 0;

    public function __construct($layoutName) {
        $this->_layout = $layoutName;
    }

    public function getName() {
        return $this->_name;
    }

    public function getLayout() {
        return $this->_layout;
    }

    public function isAutoEntered() {
        return $this->_autoEntered;
    }

    public function isGlobal() {
        return $this->_global;
    }

    public function getRepetitionCount() {
        return $this->_maxRepeat;
    }

    public function validate($value, $validationError = null) {
        $isValid = true;
        if ($validationError === null) {
            $isValid = false;
            $validationError = new FileMaker_Error_Validation($this->_layout->_impl->_fm);
        }
        foreach ($this->getValidationRules() as $rule) {
            switch ($rule) {
                case FILEMAKER_RULE_NOTEMPTY:
                    if (empty($value)) {
                        $validationError->addError($this, $rule, $value);
                    }
                    break;
                case FILEMAKER_RULE_NUMERICONLY :
                    if (!empty($value)) {
                        if ($this->checkNumericOnly($value)) {
                            $validationError->addError($this, $rule, $value);
                        }
                    }
                    break;
                case FILEMAKER_RULE_MAXCHARACTERS :
                    if (!empty($value)) {
                        $strlen = strlen($value);
                        if ($strlen > $this->_maxCharacters) {
                            $validationError->addError($this, $rule, $value);
                        }
                    }
                    break;
                case FILEMAKER_RULE_TIME_FIELD :
                    if (!empty($value)) {
                        if (!$this->checkTimeFormat($value)) {

                            $validationError->addError($this, $rule, $value);
                        } else {
                            $this->checkTimeValidity($value, $rule, $validationError, FALSE);
                        }
                    }
                    break;
                case FILEMAKER_RULE_TIMESTAMP_FIELD :
                    if (!empty($value)) {
                        if (!$this->checkTimeStampFormat($value)) {

                            $validationError->addError($this, $rule, $value);
                        } else {
                            $this->checkDateValidity($value, $rule, $validationError);
                            $this->checkTimeValidity($value, $rule, $validationError, FALSE);
                        }
                    }
                    break;
                case FILEMAKER_RULE_DATE_FIELD :
                    if (!empty($value)) {
                        if (!$this->checkDateFormat($value)) {

                            $validationError->addError($this, $rule, $value);
                        } else {
                            $this->checkDateValidity($value, $rule, $validationError);
                        }
                    }
                    break;
                case FILEMAKER_RULE_FOURDIGITYEAR :
                    if (!empty($value)) {
                        switch ($this->_result) {
                            case 'timestamp' :
                                if ($this->checkTimeStampFormatFourDigitYear($value)) {
                                    preg_match('#^([0-9]{1,2})[-,/,\\\\]([0-9]{1,2})[-,/,\\\\]([0-9]{4})#', $value, $matches);
                                    $month = $matches[1];
                                    $day = $matches[2];
                                    $year = $matches[3];
                                    if ($year < 1 || $year > 4000) {
                                        $validationError->addError($this, $rule, $value);
                                    } else
                                    if (!checkdate($month, $day, $year)) {
                                        $validationError->addError($this, $rule, $value);
                                    } else {
                                        $this->checkTimeValidity($value, $rule, $validationError, FALSE);
                                    }
                                } else {

                                    $validationError->addError($this, $rule, $value);
                                }
                                break;
                            default :
                                preg_match('#([0-9]{1,2})[-,/,\\\\]([0-9]{1,2})[-,/,\\\\]([0-9]{1,4})#', $value, $matches);
                                if (count($matches) != 3) {
                                    $validationError->addError($this, $rule, $value);
                                } else {
                                    $strlen = strlen($matches[2]);
                                    if ($strlen != 4) {
                                        $validationError->addError($this, $rule, $value);
                                    } else {
                                        if ($matches[2] < 1 || $matches[2] > 4000) {
                                            $validationError->addError($this, $rule, $value);
                                        } else {
                                            if (!checkdate($matches[0], $matches[1], $matches[2])) {
                                                $validationError->addError($this, $rule, $value);
                                            }
                                        }
                                    }
                                }
                                break;
                        }
                    }
                    break;
                case FILEMAKER_RULE_TIMEOFDAY :
                    if (!empty($value)) {
                        if ($this->checkTimeFormat($value)) {
                            $this->checkTimeValidity($value, $rule, $validationError, TRUE);
                        } else {

                            $validationError->addError($this, $rule, $value);
                        }
                    }
                    break;
            }
        }
        if ($isValid) {
            return $validationError;
        } else {
            return $validationError->numErrors() ? $validationError : true;
        }
    }

    public function getLocalValidationRules() {
        $rules = array();
        foreach (array_keys($this->_validationRules) as $rule) {
            switch ($rule) {
                case FILEMAKER_RULE_NOTEMPTY :
                    $rules[] = $rule;
                    break;
                case FILEMAKER_RULE_NUMERICONLY :
                    $rules[] = $rule;
                    break;
                case FILEMAKER_RULE_MAXCHARACTERS :
                    $rules[] = $rule;
                    break;
                case FILEMAKER_RULE_FOURDIGITYEAR :
                    $rules[] = $rule;
                    break;
                case FILEMAKER_RULE_TIMEOFDAY :
                    $rules[] = $rule;
                    break;
                case FILEMAKER_RULE_TIMESTAMP_FIELD :
                    $rules[] = $rule;
                    break;
                case FILEMAKER_RULE_DATE_FIELD :
                    $rules[] = $rule;
                    break;
                case FILEMAKER_RULE_TIME_FIELD :
                    $rules[] = $rule;
                    break;
            }
        }
        return $rules;
    }

    public function checkTimeStampFormatFourDigitYear($value) {
        return (preg_match('#^[ ]*([0-9]{1,2})[-,/,\\\\]([0-9]{1,2})[-,/,\\\\]([0-9]{4})[ ]*([0-9]{1,2})[:]([0-9]{1,2})([:][0-9]{1,2})?([ ]*((AM|PM)|(am|pm)))?[ ]*$#', $value));
    }

    public function checkTimeStampFormat($value) {
        return (preg_match('#^[ ]*([0-9]{1,2})[-,/,\\\\]([0-9]{1,2})([-,/,\\\\]([0-9]{1,4}))?[ ]*([0-9]{1,2})[:]([0-9]{1,2})([:][0-9]{1,2})?([ ]*((AM|PM)|(am|pm)))?[ ]*$#', $value));
    }

    public function checkDateFormat($value) {
        return (preg_match('#^[ ]*([0-9]{1,2})[-,/,\\\\]([0-9]{1,2})([-,/,\\\\]([0-9]{1,4}))?[ ]*$#', $value));
    }

    public function checkTimeFormat($value) {
        return (preg_match('#^[ ]*([0-9]{1,2})[:]([0-9]{1,2})([:][0-9]{1,2})?([ ]*((AM|PM)|(am|pm)))?[ ]*$#', $value));
    }

    public function checkNumericOnly($value) {
        return (!is_numeric($value));
    }

    public function checkDateValidity($value, $rule, $validationError) {
        preg_match('#([0-9]{1,2})[-,/,\\\\]([0-9]{1,2})([-,/,\\\\]([0-9]{1,4}))?#', $value, $matches);
        if ($matches[4]) {
            $strlen = strlen($matches[4]);
            $year = $matches[4];
            if ($strlen != 4) {
                $year = $year + 2000;
            }
            if ($matches[4] < 1 || $matches[4] > 4000) {
                $validationError->addError($this, $rule, $value);
            } else {
                if (!checkdate($matches[1], $matches[2], $matches[4])) {
                    $validationError->addError($this, $rule, $value);
                }
            }
        } else {
            $year = date('Y');
            if (!checkdate($matches[1], $matches[2], $year)) {
                $validationError->addError($this, $rule, $value);
            }
        }
    }

    public function checkTimeValidity($value, $rule, $validationError, $shortHoursFormat) {
        $format = 0;
        if ($shortHoursFormat) {
            $format = 12;
        } else {
            $format = 24;
        }
        preg_match('#([0-9]{1,2})[:]([0-9]{1,2})[:]?([0-9]{1,2})?#', $value, $matches);
        $hours = $matches[1];
        $minutes = $matches[2];
        if (count($matches) >= 4) {
            $seconds = $matches[3];
        }
        if ($hours < 0 || $hours > $format) {
            $validationError->addError($this, $rule, $value);
        } else if ($minutes < 0 || $minutes > 59) {
            $validationError->addError($this, $rule, $value);
        } else
        if (isset($seconds)) {
            if ($seconds < 0 || $seconds > 59)
                $validationError->addError($this, $rule, $value);
        }
    }

    public function getValidationRules() {
        return array_keys($this->_validationRules);
    }

    public function getValidationMask() {
        return $this->_validationMask;
    }

    public function hasValidationRule($field) {
        return $field & $this->_validationMask;
    }

    public function describeValidationRule($rule) {
        if (is_array($this->_validationRules[$rule])) {
            return $this->_validationRules[$rule];
        }
        return null;
    }

    public function describeLocalValidationRules() {
        $rules = array();
        foreach ($this->_validationRules as $rule => $description) {
            switch ($rule) {
                case FILEMAKER_RULE_NOTEMPTY :
                    $rules[$rule] = $description;
                    break;
                case FILEMAKER_RULE_NUMERICONLY :
                    $rules[$rule] = $description;
                    break;
                case FILEMAKER_RULE_MAXCHARACTERS :
                    $rules[$rule] = $description;
                    break;
                case FILEMAKER_RULE_FOURDIGITYEAR :
                    $rules[$rule] = $description;
                    break;
                case FILEMAKER_RULE_TIMEOFDAY :
                    $rules[$rule] = $description;
                    break;
                case FILEMAKER_RULE_TIMESTAMP_FIELD :
                    $rules[$rule] = $description;
                    break;
                case FILEMAKER_RULE_DATE_FIELD :
                    $rules[$rule] = $description;
                    break;
                case FILEMAKER_RULE_TIME_FIELD :
                    $rules[$rule] = $description;
                    break;
            }
        }
        return $rules;
    }

    public function describeValidationRules() {
        return $this->_validationRules;
    }

    public function getResult() {
        return $this->_result;
    }

    public function getType() {
        return $this->_type;
    }

    public function getValueList($listName = null) {
        $extendedInfos = $this->_layout->loadExtendedInfo($listName);
        if (FileMaker::isError($extendedInfos)) {
            return $extendedInfos;
        }
        return $this->_layout->getValueList($this->_valueList);
    }

    public function getStyleType() {
        $extendedInfos = $this->_layout->loadExtendedInfo();
        if (FileMaker::isError($extendedInfos)) {
            return $extendedInfos;
        }
        return $this->_styleType;
    }

}
