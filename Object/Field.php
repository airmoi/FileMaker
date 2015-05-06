<?php
namespace airmoi\FileMaker\Object;
use airmoi\FileMaker\FileMaker;
/**
 * FileMaker API for PHP
 *
 * @package FileMaker
 *
 * Copyright � 2005-2009, FileMaker, Inc. All rights reserved.
 * NOTE: Use of this source code is subject to the terms of the FileMaker
 * Software License which accompanies the code. Your use of this source code
 * signifies your agreement to such license terms and conditions. Except as
 * expressly granted in the Software License, no other copyright, patent, or
 * other intellectual property license or right is granted, either expressly or
 * by implication, by FileMaker.
 */

/**
 * @ignore Load delegate.
 */
//require_once dirname(__FILE__) . '/Implementation/FieldImpl.php';


/**
 * Field description class. Contains all the information about a
 * specific field on a layout.
 *
 * @package FileMaker
 */
class Field
{
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

    /**
     * Field object constructor.
     *
     * @param FileMaker_Layout $layout Parent Layout object.
     */
    public function __construct(Layout $layout)
    {
        $this->_layout = $layout;
    }

    /**
     * Returns the name of this field.
     *
     * @return string Field name.
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Returns the FileMaker_Layout object that contains this field.
     *
     * @return FileMaker_Layout Layout object.
     */
    public function getLayout()
    {
        return $this->_layout;
    }

    /**
     * Returns TRUE if data in this field is auto-entered or FALSE 
     * if it is entered manually.
     *
     * @return boolean Auto-entered status of this field.
     */
    public function isAutoEntered()
    {
        return $this->_autoEntered;
    }

    /**
     * Returns TRUE if this field is global or FALSE if it is not.
     *
     * @return boolean Global status of this field.
     */
    public function isGlobal()
    {
        return $this->_global;
    }

    /**
     * Returns the maximum number of repetitions for this field.
     *
     * @return integer Maximum repetitions of this field.
     */
    public function getRepetitionCount()
    {
        return $this->_maxRepeat;
    }

    /**
     * Returns TRUE if $value is valid for this field, or a
     * FileMaker_Error_Validation object describing how pre-validation
     * failed.
     *
     * @param mixed $value Value to pre-validate.
     * @param FileMaker_Error_Validation $error If pre-validation is being 
     *        done on more than one field, you may pass validate() an existing 
     *        error object to add pre-validation failures to.$error is not 
     *        passed by reference, though, so you must catch the return value 
     *        of validate() and use it as the new $error object. This method 
     *        never overwrites an existing $error object with boolean TRUE.
     *
     * @return boolean|FileMaker_Error_Validation Result of field 
     *         pre-validation on $value.
     */
    public function validate($value, $error = null)
    {
        $isValid = true;
        if ($error === null) {
            $isValid = false;
            $error = new FileMaker_Error_Validation($this->_layout->_impl->_fm);
        }
        foreach ($this->getValidationRules() as $rule) {
            switch ($rule) {
                case FILEMAKER_RULE_NOTEMPTY:
                    if (empty($value)) {
                        $error->addError($this, $rule, $value);
                    }
                    break;
                case FILEMAKER_RULE_NUMERICONLY :
                    if (!empty($value)) {
                        if ($this->checkNumericOnly($value)) {
                            $error->addError($this, $rule, $value);
                        }
                    }
                    break;
                case FILEMAKER_RULE_MAXCHARACTERS :
                    if (!empty($value)) {
                        $strlen = strlen($value);
                        if ($strlen > $this->_maxCharacters) {
                            $error->addError($this, $rule, $value);
                        }
                    }
                    break;
                case FILEMAKER_RULE_TIME_FIELD :
                    if (!empty($value)) {
                        if (!$this->checkTimeFormat($value)) {

                            $error->addError($this, $rule, $value);
                        } else {
                            $this->checkTimeValidity($value, $rule, $error, FALSE);
                        }
                    }
                    break;
                case FILEMAKER_RULE_TIMESTAMP_FIELD :
                    if (!empty($value)) {
                        if (!$this->checkTimeStampFormat($value)) {

                            $error->addError($this, $rule, $value);
                        } else {
                            $this->checkDateValidity($value, $rule, $error);
                            $this->checkTimeValidity($value, $rule, $error, FALSE);
                        }
                    }
                    break;
                case FILEMAKER_RULE_DATE_FIELD :
                    if (!empty($value)) {
                        if (!$this->checkDateFormat($value)) {

                            $error->addError($this, $rule, $value);
                        } else {
                            $this->checkDateValidity($value, $rule, $error);
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
                                        $error->addError($this, $rule, $value);
                                    } else
                                    if (!checkdate($month, $day, $year)) {
                                        $error->addError($this, $rule, $value);
                                    } else {
                                        $this->checkTimeValidity($value, $rule, $error, FALSE);
                                    }
                                } else {

                                    $error->addError($this, $rule, $value);
                                }
                                break;
                            default :
                                preg_match('#([0-9]{1,2})[-,/,\\\\]([0-9]{1,2})[-,/,\\\\]([0-9]{1,4})#', $value, $matches);
                                if (count($matches) != 3) {
                                    $error->addError($this, $rule, $value);
                                } else {
                                    $strlen = strlen($matches[2]);
                                    if ($strlen != 4) {
                                        $error->addError($this, $rule, $value);
                                    } else {
                                        if ($matches[2] < 1 || $matches[2] > 4000) {
                                            $error->addError($this, $rule, $value);
                                        } else {
                                            if (!checkdate($matches[0], $matches[1], $matches[2])) {
                                                $error->addError($this, $rule, $value);
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
                            $this->checkTimeValidity($value, $rule, $error, TRUE);
                        } else {

                            $error->addError($this, $rule, $value);
                        }
                    }
                    break;
            }
        }
        if ($isValid) {
            return $error;
        } else {
            return $error->numErrors() ? $error : true;
        }
    }

    /**
     * Returns an array of FILEMAKER_RULE_* constants for each rule 
     * set on this field that can be evaluated by the PHP engine. 
     * 
     * Rules such as "unique" and "exists" can only be pre-validated on the 
     * Database Server and are not included in this list.
     *
     * @return array Local rule array.
     */
    public function getLocalValidationRules()
    {
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

    /**
     * Returns an array of FILEMAKER_RULE_* constants for each rule 
     * set on this field.
     *
     * @return array Rule array.
     */
    public function getValidationRules()
    {
        return array_keys($this->_validationRules);
    }

    /**
     * Returns the full additive bitmask of pre-validation rules for this
     * field.
     *
     * @return integer Rule bitmask.
     */
    public function getValidationMask()
    {
        return $this->_validationMask;
    }

    /**
     * Returns TRUE if the specified FILEMAKER_RULE_* constant matches the
     * field's pre-validation bitmask. Otherwise, returns FALSE.
     *
     * @param integer $validationRule Pre-validation rule constant to test.
     *
     * @return boolean
     */
    public function hasValidationRule($validationRule)
    {
         return $validationRule & $this->_validationMask;
    }

    /**
     * Returns any additional information for the specified pre-validation 
     * rule. 
     *
     * Used for range rules and other rules that have additional 
     * pre-validation parameters.
     *
     * @param integer $validationRule FILEMAKER_RULE_* constant 
     *        to get information for. 
     * 
     * @return array Any extra information for $validationRule.
     */
    public function describeValidationRule($validationRule)
    {
        if (is_array($this->_validationRules[$validationRule])) {
            return $this->_validationRules[$validationRule];
        }
        return null;
    }

    /**
     * Return an array of arrays containing the extra information for 
     * all pre-validation rules on this field that can be evaluated by the 
     * PHP engine. 
     * 
     * Rules such as "unique" and "exists" can be validated only 
     * on the Database Server and are not included in this list. 
     * Indexes of the outer array are FILEMAKER_RULE_* constants, 
     * and values are the same array returned by describeValidationRule().
     *
     * @return array An associative array of all extra pre-validation 
     *         information, with rule constants as indexes and extra 
     *         information as the values.
     */
    public function describeLocalValidationRules()
    {
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

    /**
     * Returns any additional information for all pre-validation rules.
     *
     * @return array An associative array of all extra pre-validation 
     *         information, with FILEMAKER_RULE_* constants 
     *         as keys and extra information as the values.
     */
    public function describeValidationRules()
    {
        return $this->_validationRules;
    }

    /**
     * Returns the result type of this field -- for example, 'text',
     * 'number', 'date', 'time', 'timestamp', or 'container'.
     *
     * @return string Result type.
     */
    public function getResult()
    {
        return $this->_result;
    }

    /**
     * Returns the type of this field -- for example, 'normal',
     * 'calculation', or 'summary'.
     *
     * @return string Type.
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Returns the list of choices from the value list associated with this 
     * field. 
     *
     * If this field is not associated with a value list, this method returns 
     * NULL.
     *
     * @param string  $recid Record from which to display the value list.
     * 
     * @return array Value list array.
     */
    public function getValueList($listName = null)
    {
        $extendedInfos = $this->_layout->loadExtendedInfo($listName);
        if (FileMaker::isError($extendedInfos)) {
            return $extendedInfos;
        }
        return $this->_layout->getValueList($this->_valueList);
    }

    /**
     * Returns the control style type of this field -- for example, 
     * 'EDITTEXT', 'POPUPLIST', 'POPUPMENU', 'CHECKBOX', 'RADIOBUTTONS' or
     * 'CALENDAR'.
     *
     * @return string Style type.
     */
    public function getStyleType()
    {
        $extendedInfos = $this->_layout->loadExtendedInfo();
        if (FileMaker::isError($extendedInfos)) {
            return $extendedInfos;
        }
        return $this->_styleType;
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

}
