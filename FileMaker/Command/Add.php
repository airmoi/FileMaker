<?php
/**
 * FileMaker API for PHP
 *
 * @package FileMaker
 *
 * Copyright © 2005-2007, FileMaker, Inc. All rights reserved.
 * NOTE: Use of this source code is subject to the terms of the FileMaker
 * Software License which accompanies the code. Your use of this source code
 * signifies your agreement to such license terms and conditions. Except as
 * expressly granted in the Software License, no other copyright, patent, or
 * other intellectual property license or right is granted, either expressly or
 * by implication, by FileMaker.
 */

/**
 * @ignore Include parent and delegate classes.
 */
require_once dirname(__FILE__) . '/../Command.php';
require_once dirname(__FILE__) . '/../Implementation/Command/AddImpl.php';


/**
 * Command class that adds a new record.
 * Create this command with {@link FileMaker::newAddCommand()}.
 *
 * @package FileMaker
 */
class FileMaker_Command_Add extends FileMaker_Command
{
    /**
     * Implementation
     *
     * @var FileMaker_Command_Add_Implementation
     * @access private
     */
    var $_impl;

    /**
     * Add command constructor.
     *
     * @ignore
     * @param FileMaker_Implementation $fm FileMaker_Implementation object the command was created by.
     * @param string $layout Layout to add a record to.
     * @param array $values Associative array of field name => value pairs. To set field repetitions,
     * use a numerically indexed array for the value of a field, with the numeric keys
     * corresponding to the repetition number to set.
     */
    function FileMaker_Command_Add($fm, $layout, $values = array())
    {
        $this->_impl = new FileMaker_Command_Add_Implementation($fm, $layout, $values);
    }

    /**
     * Sets the new value for a field.
     *
     * @param string $field Name of field to set.
     * @param string $value Value to set for this field.
     * @param integer $repetition Field repetition number to set,
     *        Defaults to the first repetition.
     */
    function setField($field, $value, $repetition = 0)
    {
        return $this->_impl->setField($field, $value, $repetition);
    }

    /**
     * Sets the new value for a date, time, or timestamp field from a
     * UNIX timestamp value. 
     *
     * If the field is not a date or time field, then this method returns  
     * an Error object. Otherwise, returns TRUE.
     *
     * If layout data for the target of this command has not already 
     * been loaded, calling this method loads layout data so that
     * the type of the field can be checked.
     *
     * @param string $field Name of the field to set.
     * @param string $timestamp Timestamp value.
     * @param integer $repetition Field repetition number to set. 
     *        Defaults to the first repetition.
     */
    function setFieldFromTimestamp($field, $timestamp, $repetition = 0)
    {
        return $this->_impl->setFieldFromTimestamp($field, $timestamp, $repetition);
    }

}
