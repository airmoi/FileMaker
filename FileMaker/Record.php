<?php
namespace airmoi\FileMaker;
use airmoi\FileMaker\Implementation;
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
 * @ignore Load delegate.
 */
//require_once dirname(__FILE__) . '/Implementation/RecordImpl.php';


/**
 * Default Record class that represents each record of a result set. 
 * 
 * From a Record object, you can get field data, edit and delete the record, 
 * get its parent record, get its related record set, and create related 
 * records. 
 * 
 * Instead of this class, you can specify a different class to use for Record 
 * objects. To specify the record class to use, open the 
 * FileMaker/conf/filemaker-api.php configuration file where the API is 
 * installed. Then set $__FM_CONFIG['recordClass'] to the name of the record 
 * class to use. The class you specify should be a subclass of the 
 * FileMaker_Record base class or encapsulate its functionality. In PHP 5, 
 * this class would implement an interface that alternate classes would be 
 * required to implement as well. 
 * 
 * @package FileMaker
 */
class FileMaker_Record
{
    /**
     * The Implementation that implements this record.
     *
     * @var FileMaker_Record_Implementation
     * @access private
     */
    private $_impl;

    /**
     * Record object constructor.
     *
     * @param FileMaker_Layout|FileMaker_RelatedSet Specify either the Layout 
     *        object associated with this record or the Related Set object 
     *        that this record is a member of.
     */
    public function __construct($layout)
    {
        $this->_impl = new FileMaker_Record_Implementation($layout);
    }

    /**
     * Returns the layout this record is associated with.
     *
     * @return FileMaker_Layout This record's layout.
     */
    public function getLayout()
    {
        return $this->_impl->getLayout();
    }

    /**
     * Returns a list of the names of all fields in the record. 
     *
     * Only the field names are returned. If you need additional 
     * information, examine the Layout object provided by the 
     * parent object's {@link FileMaker_Result::getLayout()} method.  
     *
     * @return array List of field names as strings.
     */
    public function getFields()
    {
        return $this->_impl->getFields();
    }

    /**
     * Returns the HTML-encoded value of the specified field.
     *
     * This method converts some special characters in the field value to 
     * HTML entities. For example, '&', '"', '<', and '>' are converted to 
     * '&amp;', '&quot;', '&lt;', and '&gt;', respectively.
     *
     * @param string $field Name of field.
     * @param integer $repetition Field repetition number to get. 
     *        Defaults to the first repetition.
     *
     * @return string Encoded field value.
     */
    public function getField($field, $repetition = 0)
    {
        return $this->_impl->getField($field, $repetition);
    }
    
	/**
     * Returns the unencoded value of the specified field.
     *
     * This method does not convert special characters in the field value to 
     * HTML entities.
     *
     * @param string $field Name of field.
     * @param integer $repetition Field repetition number to get. 
     *        Defaults to the first repetition.
     *
     * @return string Unencoded field value.
     */
    public function getFieldUnencoded($field, $repetition = 0)
    {
        return $this->_impl->getFieldUnencoded($field, $repetition);
    }

    /**
     * Returns the value of the specified field as a UNIX 
     * timestamp. 
     * 
     * If the field is a date field, the timestamp is
     * for the field date at midnight. It the field is a time field,
     * the timestamp is for that time on January 1, 1970. Timestamp
     * (date and time) fields map directly to the UNIX timestamp. If the
     * specified field is not a date or time field, or if the timestamp
     * generated would be out of range, then this method returns a
     * FileMaker_Error object instead.
     * 
     * @param string $field Name of the field.
     * @param integer $repetition Field repetition number to get. 
     *        Defaults to the first repetition.
     *
     * @return integer Timestamp value.
     */
    public function getFieldAsTimestamp($field, $repetition = 0)
    {
        return $this->_impl->getFieldAsTimestamp($field, $repetition);
    }

    /**
     * Sets the value of $field.
     *
     * @param string $field Name of the field.
     * @param string $value New value of the field.
     * @param integer $repetition Field repetition number to set. 
     *        Defaults to the first repetition.
     */
    public function setField($field, $value, $repetition = 0)
    {
        return $this->_impl->setField($field, $value, $repetition);
    }

    /**
     * Sets the new value for a date, time, or timestamp field from a
     * UNIX timestamp value. 
     *
     * If the field is not a date or time field, then returns an error. 
     * Otherwise, returns TRUE.
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
    public function setFieldFromTimestamp($field, $timestamp, $repetition = 0)
    {
        return $this->_impl->setFieldFromTimestamp($field, $timestamp, $repetition);
    }

    /**
     * Returns the record ID of this object.
     *
     * @return string Record ID.
     */
    public function getRecordId()
    {
        return $this->_impl->getRecordId();
    }

    /**
     * Returns the modification ID of this record.
     * 
     * The modification ID is an incremental counter that specifies the current 
     * version of a record. See the {@link FileMaker_Command_Edit::setModificationId()} 
     * method.
     *
     * @return integer Modification ID.
     */
    public function getModificationId()
    {
        return $this->_impl->getModificationId();
    }

    /**
     * Returns any Record objects in the specified portal or a FileMaker_Error
     * object if there are no related records
     *
     * @param string $relatedSet Name of the portal to return records from.
     *
     * @return array Array of FileMaker_Record objects from $relatedSet|FileMaker_Error object.
     */
    public function getRelatedSet($relatedSet)
    {
        return $this->_impl->getRelatedSet($relatedSet);
    }

    /**
     * Creates a new record in the specified portal.
     *
     * @param string $relatedSet Name of the portal to create a new record in.
     *
     * @return FileMaker_Record A new, blank record.
     */
    public function newRelatedRecord($relatedSet)
    {
        return $this->_impl->newRelatedRecord($this, $relatedSet);
    }

    /**
     * Returns the parent record, if this record is a child record in a portal.
     *
     * @return FileMaker_Record Parent record.
     */
    public function getParent()
    {
        return $this->_impl->getParent();
    }

    /**
     * Pre-validates either a single field or the entire record.
     * 
     * This method uses the pre-validation rules that are enforceable by the 
     * PHP engine -- for example, type rules, ranges, and four-digit dates. 
     * Rules such as "unique" or "existing," or validation by calculation 
     * field, cannot be pre-validated.
     *
     * If you pass the optional $fieldName argument, only that field is 
     * pre-validated. Otherwise, the record is pre-validated as if commit()
     * were called with "Enable record data pre-validation" selected in
     * FileMaker Server Admin Console. If pre-validation passes, validate() 
     * returns TRUE. If pre-validation fails, then validate() returns a 
     * FileMaker_Error_Validation object containing details about what failed 
     * to pre-validate.
     *
     * @param string $fieldName Name of field to pre-validate. If empty, 
     *        pre-validates the entire record.
     *
     * @return boolean|FileMaker_Error_Validation TRUE, if pre-validation 
     *         passes for $value. Otherwise, an Error Validation object.
     */
    public function validate($fieldName = null)
    {
        return $this->_impl->validate($fieldName);
    }

    /**
     * Saves any changes to this record in the database on the Database Server.
     *
     * @return boolean|FileMaker_Error TRUE, if successful. Otherwise, an Error
     *         object.
     */
    public function commit()
    {
        return $this->_impl->commit();
    }

    /**
     * Deletes this record from the database on the Database Server.
     *
     * @return FileMaker_Result Response object.
     */
    public function delete()
    {
        return $this->_impl->delete();
    }
    
    
    /**
     * Gets a specific related record. 
     *
     * @access private
     *
     * @param string $relatedSetName Name of the portal.
     * @param string $recordId Record ID of the record in the portal.
     * 
     * @return FileMaker_Response Response object.
     */
    public function getRelatedRecordById($relatedSetName, $recordId)
    {	
    	return $this->_impl->getRelatedRecordById($relatedSetName, $recordId);	
    }

}
