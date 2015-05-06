<?php
namespace airmoi\FileMaker;
use airmoi\FileMaker\Implementation as Impl;
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
class FileMaker_Field
{
    /**
     * Implementation. This is the object that actually implements the
     * layout functionality.
     *
     * @var Impl\FileMaker_Layout_Implementation
     * @access private
     */
    private $_impl;

    /**
     * Field object constructor.
     *
     * @param FileMaker_Layout $layout Parent Layout object.
     */
    public function FileMaker_Field($layout)
    {
        $this->_impl = new Impl\FileMaker_Field_Implementation($layout);
    }

    /**
     * Returns the name of this field.
     *
     * @return string Field name.
     */
    public function getName()
    {
        return $this->_impl->getName();
    }

    /**
     * Returns the FileMaker_Layout object that contains this field.
     *
     * @return FileMaker_Layout Layout object.
     */
    public function getLayout()
    {
        return $layout = $this->_impl->getLayout();
    }

    /**
     * Returns TRUE if data in this field is auto-entered or FALSE 
     * if it is entered manually.
     *
     * @return boolean Auto-entered status of this field.
     */
    public function isAutoEntered()
    {
        return $this->_impl->isAutoEntered();
    }

    /**
     * Returns TRUE if this field is global or FALSE if it is not.
     *
     * @return boolean Global status of this field.
     */
    public function isGlobal()
    {
        return $this->_impl->isGlobal();
    }

    /**
     * Returns the maximum number of repetitions for this field.
     *
     * @return integer Maximum repetitions of this field.
     */
    public function getRepetitionCount()
    {
        return $this->_impl->getRepetitionCount();
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
        return $this->_impl->validate($value, $error);
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
        return $this->_impl->getLocalValidationRules();
    }

    /**
     * Returns an array of FILEMAKER_RULE_* constants for each rule 
     * set on this field.
     *
     * @return array Rule array.
     */
    public function getValidationRules()
    {
        return $this->_impl->getValidationRules();
    }

    /**
     * Returns the full additive bitmask of pre-validation rules for this
     * field.
     *
     * @return integer Rule bitmask.
     */
    public function getValidationMask()
    {
        return $this->_impl->getValidationMask();
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
        return $this->_impl->hasValidationRule($validationRule);
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
        return $this->_impl->describeValidationRule($validationRule);
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
        return $this->_impl->describeLocalValidationRules();
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
        return $this->_impl->describeValidationRules();
    }

    /**
     * Returns the result type of this field -- for example, 'text',
     * 'number', 'date', 'time', 'timestamp', or 'container'.
     *
     * @return string Result type.
     */
    public function getResult()
    {
        return $this->_impl->getResult();
    }

    /**
     * Returns the type of this field -- for example, 'normal',
     * 'calculation', or 'summary'.
     *
     * @return string Type.
     */
    public function getType()
    {
        return $this->_impl->getType();
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
    public function getValueList($recid = null)
    {
        return $this->_impl->getValueList($recid);
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
        return $this->_impl->getStyleType();
    }

}
