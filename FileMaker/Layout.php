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
 * @ignore Load delegate and field classes.
 */
//require_once dirname(__FILE__) . '/Implementation/LayoutImpl.php';

//require_once dirname(__FILE__) . '/Field.php';


/**
 * Layout description class. Contains all the information about a
 * specific layout. Can be requested directly or returned as part of
 * a result set.
 *
 * @package FileMaker
 */
class FileMaker_Layout
{
    /**
     * Implementation. This is the object that actually implements the
     * layout functionality.
     *
     * @private Impl\FileMaker_Layout_Implementation
     * @access private
     */
    private $_impl;

    /**
     * Layout object constructor.
     *
     * @param FileMaker_Implementation &$fm FileMaker_Implementation object 
     *        that this layout was created through.
     */
    public function FileMaker_Layout(FileMaker_Implementation $fm)
    {
        $this->_impl = new Impl\FileMaker_Layout_Implementation($fm);
    }

    /**
     * Returns the name of this layout.
     *
     * @return string Layout name.
     */
    public function getName()
    {
        return $this->_impl->getName();
    }

    /**
     * Returns the name of the database that this layout is in.
     *
     * @return string Database name.
     */
    public function getDatabase()
    {
        return $this->_impl->getDatabase();
    }

    /**
     * Returns an array with the names of all fields in this layout.
     *
     * @return array List of field names as strings.
     */
    public function listFields()
    {
        return $this->_impl->listFields();
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
        return $this->_impl->getField($fieldName);
    }

    /**
     * Returns an associative array with the names of all fields as
     * keys and FileMaker_Field objects as the array values.
     *
     * @return array Array of {@link FileMaker_Field} objects.
     */
    public function getFields()
    {
        return $this->_impl->getFields();
    }

    /**
     * Returns an array of related table names for all portals on
     * this layout.
     *
     * @return array List of related table names as strings.
     */
    public function listRelatedSets()
    {
        return $this->_impl->listRelatedSets();
    }

    /**
     * Returns a FileMaker_RelatedSet object that describes the specified 
     * portal.
     *
     * @param string $relatedSet Name of the related table for a portal.
     *
     * @return FileMaker_RelatedSet|FileMaker_Error RelatedSet object, if 
     *         successful. Otherwise, an Error object.
     */
    public function getRelatedSet($relatedSet)
    {
        return $this->_impl->getRelatedSet($relatedSet);
    }

    /**
     * Returns an associative array with the related table names of all 
     * portals as keys and FileMaker_RelatedSet objects as the array values.
     *
     * @return array Array of {@link FileMaker_RelatedSet} objects.
     */
    public function getRelatedSets()
    {
        return $this->_impl->getRelatedSets();
    }

    /**
     * Returns the names of any value lists associated with this
     * layout.
     *
     * @return array List of value list names as strings.
     */
    public function listValueLists()
    {
        return $this->_impl->listValueLists();
    }

    /**
     * Returns the list of defined values in the specified value list.
     *
     * @param string $valueList Name of value list.
     * @param string  $recid Record from which the value list should be 
     *        displayed.
     *
     * @return array List of defined values.

     * @deprecated Use getValueListTwoFields instead.

     * @see getValueListTwoFields
     */
    public function getValueList($valueList, $recid = null)
    {
        return $this->_impl->getValueList($valueList, $recid);
    }

    

    /**

     * Returns the list of defined values in the specified value list. 

     * This method supports single, 2nd only, and both fields value lists. 

     *

     * @param string $valueList Name of value list.

     * @param string  $recid Record from which the value list should be 

     *        displayed.

     *

     * @return array of display names and its corresponding 

     * value from the value list.

     */

    public function getValueListTwoFields($valueList, $recid = null)

    {

        return $this->_impl->getValueListTwoFields($valueList, $recid);

    }

    /**
     * Returns a multi-level associative array of value lists. 
     * The top-level array has names of value lists as keys and arrays as 
     * values. The second level arrays are the lists of defined values from 
     * each value list.
     *
     * @param string  $recid Record from which the value list should be 
     *        displayed.
     * 
     * @return array Array of value-list arrays.

     * @deprecated Use getValueListTwoFields instead.

     * @see getValueListsTwoFields
     */
    public function getValueLists($recid = null)
    {
        return $this->_impl->getValueLists($recid);
    }

    

    /**

     * Returns a multi-level associative array of value lists. 

     * The top-level array has names of value lists as keys and associative arrays as 

     * values. The second level associative arrays are lists of display name and its corresponding 

     * value from the value list.

     *

     * @param string  $recid Record from which the value list should be 

     *        displayed.

     * 

     * @return array Array of value-list associative arrays.

     */

    public function getValueListsTwoFields($recid = null)

    {

        return $this->_impl->getValueListsTwoFields($recid);

    }

    /**
     * Loads extended (FMPXMLLAYOUT) layout information.
     *
     * @access private
     *
     * @param string  $recid Record from which to load extended information. 
     *
     * @return boolean|FileMaker_Error TRUE, if successful. Otherwise, an 
     *         Error object.
     */
    public function loadExtendedInfo($recid = null)
    {
        return $this->_impl->loadExtendedInfo($recid);
    }

}
