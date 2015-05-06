<?php
namespace airmoi\FileMaker\Object;
use airmoi\FileMaker\FileMaker;
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
 * @ignore Include delegate.
 */
//require_once dirname(__FILE__) . '/Implementation/RelatedSetImpl.php';


/**
 * Portal description class. Contains all the information about a
 * specific set of related records defined by a portal on a layout.
 *
 * @package FileMaker
 */
class RelatedSet
{
    /**
     * Portal constructor.
     *
     * @param Layout &$layout Layout object that this 
     * portal is on.
     */
    public function __construct($layout)
    {
        $this->_layout = $layout;
        $this->_fm = $layout->_impl->_fm;
    }

    /**
     * Returns the name of the related table from which this portal displays 
     * related records.
     *
     * @return string Name of related table for this portal.
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Returns an array of the names of all fields in this portal.
     *
     * @return array List of field names as strings.
     */
    public function listFields()
    {
        return array_keys($this->_fields);
    }

    /**
     * Returns a FileMaker_Field object that describes the specified field.
     *
     * @param string $fieldName Name of field.
     *
     * @return FileMaker_Field|FileMaker_Error Field object, if successful. 
     *         Otherwise, an Error object.
     */
    public function getField($fieldName)
    {
        if (isset($this->_fields[$fieldName])) {
            return $this->_fields[$fieldName];
        }
        return new FileMaker_Error($this->_fm, 'Field Not Found');
    }

    /**
     * Returns an associative array with the names of all fields as keys and 
     * FileMaker_Field objects as the array values.
     *
     * @return array Array of {@link FileMaker_Field} objects.
     */
    public function getFields()
    {
        return $this->_fields;
    }

    /**
     * Loads extended (FMPXMLLAYOUT) layout information.
     *
     * @access private
     *
     * @return boolean|FileMaker_Error TRUE, if successful. Otherwise, an Error 
     *         object.
     */
    public function loadExtendedInfo()
    {
        return new FileMaker_Error($this->_fm, 'Related sets do not support extended information.');
    }

}
