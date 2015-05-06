<?php
namespace airmoi\FileMaker;
/**
 * FileMaker API for PHP
 *
 * @package FileMaker
 *
 * Copyright � 2005-2007, FileMaker, Inc. All rights reserved.
 * NOTE: Use of this source code is subject to the terms of the FileMaker
 * Software License which accompanies the code. Your use of this source code
 * signifies your agreement to such license terms and conditions. Except as
 * expressly granted in the Software License, no other copyright, patent, or
 * other intellectual property license or right is granted, either expressly or
 * by implication, by FileMaker.
 */


/**
 * Extension of the PEAR_Error class for use in all FileMaker classes.
 *
 * @package FileMaker
 */
class FileMakerException extends \Exception
{
    private $_fm;
    /**
     * Overloaded FileMaker_Error constructor.
     *
     * @param FileMaker_Delegate &$fm FileMaker_Delegate object this error 
     *        came from.
     * @param string $message Error message.
     * @param integer $code Error code.
     */
    public function __construct($fm, $message = null, $code = null, $previous=null)
    {
        $this->_fm = $fm;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Overloads getMessage() to return an equivalent FileMaker Web Publishing 
     * Engine error if no message is explicitly set and this object has an 
     * error code.
     * 
     * @return string Error message.
     */
    public function getMessage()
    {
        if ($this->message === null && $this->getCode() !== null) {
            return $this->getErrorString();
        }
        return parent::getMessage();
    }

    /**
     * Returns the string representation of $this->code in the language 
     * currently  set for PHP error messages in FileMaker Server Admin 
     * Console.
     * 
     * You should call getMessage() in most cases, if you are not sure whether
     * the error is a FileMaker Web Publishing Engine error with an error code.
     *
     * @return string Error description.
     */
    public function getErrorString()
    {
        // Default to English.
        $lang = basename($this->_fm->getProperty('locale'));
        if (!$lang) {
            $lang = 'en';
        }

        static $strings = array();
        if (empty($strings[$lang])) {
            if (!@include_once dirname(__FILE__) . '/Error/' . $lang . '.php') {
                include_once dirname(__FILE__) . '/Error/en.php';
            }
            $strings[$lang] = $__FM_ERRORS;
        }

        if (isset($strings[$lang][$this->getCode()])) {
            return $strings[$lang][$this->getCode()];
        }

        return $strings[$lang][-1];
    }

    /**
     * Indicates whether the error is a detailed pre-validation error  
     * or a FileMaker Web Publishing Engine error.
     *
     * @return boolean FALSE, to indicate that this is an error from the 
     *         Web Publishing Engine.
     */
    public function isValidationError()
    {
        return false;
    }

}
