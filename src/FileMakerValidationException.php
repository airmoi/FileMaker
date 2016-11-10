<?php
/**
 * @copyright Copyright (c) 2016 by 1-more-thing (http://1-more-thing.com) All rights reserved.
 * @license BSD
 */
namespace airmoi\FileMaker;

use airmoi\FileMaker\Object\Field;

/**
 * Extension of the FileMakerException class that adds information about
 * pre-validation errors.
 *
 * @package FileMaker
 */
class FileMakerValidationException extends FileMakerException
{
    /**
     * Error array.
     *
     * @var array
     * @access private
     */
    public $_errors = array();

    /**
     * Adds an error.
     *
     * @param Field $field Field object that failed pre-validation.
     * @param integer $rule Pre-validation rule that failed specified as one
     *        of the FileMaker::RULE_* constants.
     * @param string $value Value that failed pre-validation.
     */
    public function addError(Field $field, $rule, $value)
    {
        $message = self::getValidationErrorString($field, $rule, $value);
        $this->_errors[] = array($field, $rule, $value, $message);
        $messages = empty($this->getMessage()) ? [] : explode("\n", $this->getMessage());
        $messages[] = $message;
        $this->message = implode("\n", $messages);
    }

    private static function getValidationErrorString(Field $field, $rule, $value)
    {
        switch ($rule) {
            case FileMaker::RULE_FOURDIGITYEAR:
                $message = 'Please enter a four digit number for the year in field %1$s';
                break;
            case FileMaker::RULE_DATE_FIELD:
                $message = 'Please enter a valid date for field %1$s';
                break;
            case FileMaker::RULE_MAXCHARACTERS:
                $message = 'Value in field %1$s exceeds maximum number of allowed characters (%2$d)';
                break;
            case FileMaker::RULE_NOTEMPTY:
                $message = 'Field %1$s is required';
                break;
            case FileMaker::RULE_NUMERICONLY:
                $message = 'Only numbers are allowed for field %1$s';
                break;
            case FileMaker::RULE_TIMEOFDAY:
            case FileMaker::RULE_TIME_FIELD:
                $message = 'Please enter a valid time in field %1$s';
                break;
            case FileMaker::RULE_TIMESTAMP_FIELD:
                $message = 'Please enter a valid timestamp in field %1$s';
                break;
            default:
                $message = 'Incorrect value for field %1$s (%3$s)';
        }

        return sprintf($message, $field->getName(), $field->maxCharacters, $value);
    }

    /**
     * Indicates whether the error is a detailed pre-validation error
     * or a FileMaker Web Publishing Engine error.
     *
     * @return boolean TRUE, to indicate that this is a pre-validation
     *         error object.
     */
    public function isValidationError()
    {
        return true;
    }

    /**
     * Returns the number of pre-validation rules that failed.
     *
     * @return integer Number of failures.
     */
    public function numErrors()
    {
        return count($this->_errors);
    }

    /**
     * Returns an array of arrays describing the pre-validation errors that
     * occurred.
     *
     * Each entry in the outer array represents a pre-validation failure.
     * Each failure is represented by a three-element array with the
     * following members:
     *
     * - 0 => The field object for the field that failed pre-validation.
     * - 1 => The pre-validation rule that failed specified as a
     *        FileMaker::RULE_* constant.
     * - 2 => The value entered for the field that failed pre-validation.
     * - 3 => The validation error message.
     *
     * Multiple pre-validation rules can fail on a single field. If you set the
     * optional $fieldName parameter, then failures for only the specified
     * field are returned.
     *
     * @param string $fieldName Name of the field to get errors for.
     *
     * @return array Pre-validation error details.
     */
    public function getErrors($fieldName = null)
    {
        if ($fieldName === null) {
            return $this->_errors;
        }

        $errors = array();
        foreach ($this->_errors as $error) {
            if ($error[0]->getName() == $fieldName) {
                $errors[] = $error;
            }
        }

        return $errors;
    }
}
