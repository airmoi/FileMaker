<?php
/**
 * @copyright Copyright (c) 2016 by 1-more-thing (http://1-more-thing.com) All rights reserved.
 * @licence BSD
 */
namespace airmoi\FileMaker;


/**
 * Extension of the Exception class for use in all FileMaker classes.
 *
 * @package FileMaker
 */
class FileMakerException extends \Exception
{
    private $_fm;
    /**
     * Overloaded Exception constructor.
     *
     * @param FileMaker $fm FileMaker object this error came from.
     * @param string $message Error message.
     * @param integer $code Error code.
     * @param null|\Exception $previous
     */
    public function __construct($fm, $message = null, $code = null, $previous=null)
    {
        $this->_fm = $fm;
        if ( $code !== null)
            $message = $this->getErrorString($code);
        
        parent::__construct($message, $code, $previous);
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
    public function getErrorString($code)
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

        if (isset($strings[$lang][$code])) {
            return $strings[$lang][$code];
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
