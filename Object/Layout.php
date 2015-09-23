<?php
namespace airmoi\FileMaker\Object;
use airmoi\FileMaker\FileMaker;
use airmoi\FileMaker\FileMakerException;
use airmoi\FileMaker\Parser\FMPXMLLAYOUT;
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
    public $fm;
    public $name;
    public $fields = array();
    public $relatedSets = array();
    public $valueLists = array();
    public $valueListTwoFields = array();
    public $database;
    public $extended = false;
    /**
     * Layout object constructor.
     *
     * @param FileMaker_Implementation &$fm FileMaker_Implementation object 
     *        that this layout was created through.
     */
    public function __construct(FileMaker $fm)
    {
        $this->fm = $fm;
    }

    /**
     * Returns the name of this layout.
     *
     * @return string Layout name.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the name of the database that this layout is in.
     *
     * @return string Database name.
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * Returns an array with the names of all fields in this layout.
     *
     * @return array List of field names as strings.
     */
    public function listFields()
    {
        return array_keys($this->fields);
    }

    /**
     * Returns a FileMaker_Field object that describes the specified field.
     *
     * @param string $fieldName Name of field.
     *
     * @return Field Field object, if successful. 
     * @throws FileMakerException
     */
    public function getField($fieldName)
    {
        if (isset($this->fields[$fieldName])) {
            return $this->fields[$fieldName];
        }
        if( $pos = strpos($fieldName, ':')){
            $relatedSet = substr($fieldName, 0, $pos);
            //$fieldName = substr($fieldName, $pos+1, strlen($fieldName));
            return $this->getRelatedSet($relatedSet)->getField($fieldName);
        }
        throw new FileMakerException($this->fm, 'Field "'.$fieldName.'" Not Found');
    }

    /**
     * Returns an associative array with the names of all fields as
     * keys and Field objects as the array values.
     *
     * @return Field[] an array of Field objects.
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Returns an array of related table names for all portals on
     * this layout.
     *
     * @return array List of related table names as strings.
     */
    public function listRelatedSets()
    {
        return array_keys($this->relatedSets);
    }

    /**
     * Returns a RelatedSet object that describes the specified 
     * portal.
     *
     * @param string $relatedSet Name of the related table for a portal.
     * @throws FileMakerException
     *
     * @return RelatedSet a RelatedSet object
     */
    public function getRelatedSet($relatedSet)
    {
         if (isset($this->relatedSets[$relatedSet])) {
            return $this->relatedSets[$relatedSet];
        }
        throw new FileMakerException($this->fm, 'RelatedSet Not Found');
    }

    /**
     * Returns an associative array with the related table names of all 
     * portals as keys and FileMaker_RelatedSet objects as the array values.
     *
     * @return RelatedSet[] Array of {@link RelatedSet} objects.
     */
    public function getRelatedSets()
    {
        return $this->relatedSets;
    }

    /**
     * Returns the names of any value lists associated with this
     * layout.
     *
     * @return array List of value list names as strings.
     * @throws FileMakerException
     */
    public function listValueLists()
    {
        $ExtendedInfos = $this->loadExtendedInfo();
        if($this->valueLists !== null)
            return array_keys($this->valueLists);
        
        return [];
    }

    /**
     * Returns the list of defined values in the specified value list.
     *
     * @param string $valueList Name of value list.
     * @param string  $recid Record from which the value list should be 
     *        displayed.
     *
     * @return array List of defined values.

     * @throws FileMakerException
     * @deprecated Use getValueListTwoFields instead.

     * @see getValueListTwoFields
     */
    public function getValueList($listName, $recid = null)
    {
        $ExtendedInfos = $this->loadExtendedInfo($recid);
        return isset($this->valueLists[$listName]) ?
                $this->valueLists[$listName] : null;
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
     * @throws FileMakerException

     * value from the value list.

     */

    public function getValueListTwoFields($valueList, $recid = null)
    {

        $ExtendedInfos = $this->loadExtendedInfo($recid);
        return isset($this->valueLists[$valueList]) ?
                $this->valueListTwoFields[$valueList] : null;

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

     * @throws FileMakerException
     * @deprecated Use getValueListTwoFields instead.

     * @see getValueListsTwoFields
     */
    public function getValueLists($recid = null)
    {
        $ExtendedInfos = $this->loadExtendedInfo($recid);
        return $this->valueLists;
    }

    

    /**

     * Returns a multi-level associative array of value lists. 

     * The top-level array has names of value lists as keys and associative arrays as 

     * values. The second level associative arrays are lists of display name and its corresponding 

     * value from the value list.

     *

     * @param string  $recid Record from which the value list should be 

     *        displayed.

     * @throws FileMakerException
     * 

     * @return array Array of value-list associative arrays.

     */

    public function getValueListsTwoFields($recid = null)

    {

        $ExtendedInfos = $this->loadExtendedInfo($recid);
        return $this->valueListTwoFields;

    }

    /**
     * Loads extended (FMPXMLLAYOUT) layout information.
     *
     * @access private
     *
     * @param string  $recid Record from which to load extended information. 
     *
     * @return boolean TRUE, if successful.
     * @throws FileMakerException;
     */
    public function loadExtendedInfo($recid = null)
    {
        if (!$this->extended || $recid != null) {

            if ($recid != null) {
                $result = $this->fm->execute(array('-db' => $this->fm->getProperty('database'),
                    '-lay' => $this->getName(),
                    '-recid' => $recid,
                    '-view' => null), 'FMPXMLLAYOUT');
            } else {
                $result = $this->fm->execute(array('-db' => $this->fm->getProperty('database'),
                    '-lay' => $this->getName(),
                    '-view' => null), 'FMPXMLLAYOUT');
            }
            $parser = new FMPXMLLAYOUT($this->fm);
            $parseResult = $parser->parse($result);
            $parser->setExtendedInfo($this);
            $this->extended = true;
        }
        return $this->extended;
    }
}
