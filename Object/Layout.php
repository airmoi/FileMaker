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
class Layout
{
    /**
     *
     * @var FileMaker 
     */
    private $_fm;
    private $_name;
    private $_fields = array();
    private $_relatedSets = array();
    private $_valueLists = array();
    private $_valueListTwoFields = array();
    private $_database;
    private $_extended = false;
    /**
     * Layout object constructor.
     *
     * @param FileMaker_Implementation &$fm FileMaker_Implementation object 
     *        that this layout was created through.
     */
    public function __construct(FileMaker $fm)
    {
        $this->_fm = $fm;
    }

    /**
     * Returns the name of this layout.
     *
     * @return string Layout name.
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Returns the name of the database that this layout is in.
     *
     * @return string Database name.
     */
    public function getDatabase()
    {
        return $this->_database;
    }

    /**
     * Returns an array with the names of all fields in this layout.
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
     * Returns an associative array with the names of all fields as
     * keys and FileMaker_Field objects as the array values.
     *
     * @return array Array of {@link FileMaker_Field} objects.
     */
    public function getFields()
    {
        return $this->_fields;
    }

    /**
     * Returns an array of related table names for all portals on
     * this layout.
     *
     * @return array List of related table names as strings.
     */
    public function listRelatedSets()
    {
        return array_keys($this->_relatedSets);
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
         if (isset($this->_relatedSets[$relatedSet])) {
            return $this->_relatedSets[$relatedSet];
        }
        return new FileMaker_Error($this->_fm, 'RelatedSet Not Found');
    }

    /**
     * Returns an associative array with the related table names of all 
     * portals as keys and FileMaker_RelatedSet objects as the array values.
     *
     * @return array Array of {@link FileMaker_RelatedSet} objects.
     */
    public function getRelatedSets()
    {
        return $this->_relatedSets;
    }

    /**
     * Returns the names of any value lists associated with this
     * layout.
     *
     * @return array List of value list names as strings.
     */
    public function listValueLists()
    {
        $ExtendedInfos = $this->loadExtendedInfo();
        if (FileMaker::isError($ExtendedInfos)) {
            return $ExtendedInfos;
        }
        return array_keys($this->_valueLists);
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
    public function getValueList($listName, $recid = null)
    {
        $ExtendedInfos = $this->loadExtendedInfo($recid);
        if (FileMaker::isError($ExtendedInfos)) {
            return $ExtendedInfos;
        }
        return isset($this->_valueLists[$listName]) ?
                $this->_valueLists[$listName] : null;
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

        $ExtendedInfos = $this->loadExtendedInfo($recid);
        if (FileMaker::isError($ExtendedInfos)) {
            return $ExtendedInfos;
        }
        return isset($this->_valueLists[$listName]) ?
                $this->_valueListTwoFields[$listName] : null;

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
        $ExtendedInfos = $this->loadExtendedInfo($recid);
        if (FileMaker::isError($ExtendedInfos)) {
            return $ExtendedInfos;
        }
        return $this->_valueLists;
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

        $ExtendedInfos = $this->loadExtendedInfo($recid);
        if (FileMaker::isError($ExtendedInfos)) {
            return $ExtendedInfos;
        }
        return $this->_valueListTwoFields;

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
        if (!$this->_extended) {

            if ($recid != null) {
                $result = $this->_fm->_execute(array('-db' => $this->_fm->getProperty('database'),
                    '-lay' => $this->getName(),
                    '-recid' => $recid,
                    '-view' => null), 'FMPXMLLAYOUT');
            } else {
                $result = $this->_fm->_execute(array('-db' => $this->_fm->getProperty('database'),
                    '-lay' => $this->getName(),
                    '-view' => null), 'FMPXMLLAYOUT');
            }
            $parser = new Parser\FileMaker_Parser_FMPXMLLAYOUT($this->_fm);
            $parseResult = $parser->parse($result);
            if (FileMaker::isError($parseResult)) {
                return $parseResult;
            }
            $parser->setExtendedInfo($this);
            $this->_extended = true;
        }
        return $this->_extended;
    }
}
