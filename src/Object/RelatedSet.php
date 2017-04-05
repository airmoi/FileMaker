<?php
/**
 * @copyright Copyright (c) 2016 by 1-more-thing (http://1-more-thing.com) All rights reserved.
 * @license BSD
 */
namespace airmoi\FileMaker\Object;

use airmoi\FileMaker\FileMaker;
use airmoi\FileMaker\FileMakerException;

/**
 * Portal description class. Contains all the information about a
 * specific set of related records defined by a portal on a layout.
 *
 * @package FileMaker
 */
class RelatedSet
{
    /**
     *
     * @var FileMaker
     */
    public $fm;
    /**
     *
     * @var Layout
     */
    public $layout;
    public $name;

    public $fields = [];

    /**
     * Portal constructor.
     *
     * @param Layout $layout Layout object where this portal is located.
     */
    public function __construct($layout)
    {
        $this->layout = $layout;
        $this->fm = $layout->fm;
    }

    /**
     * Returns the name of the related table from which this portal displays
     * related records.
     *
     * @return string Name of related table for this portal.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns an array of the names of all fields in this portal.
     *
     * @return array List of field names as strings.
     */
    public function listFields()
    {
        return array_keys($this->fields);
    }

    /**
     * Returns a Field object that describes the specified field.
     *
     * @param string $fieldName Name of field.
     *
     * @return Field|FileMakerException Field object, if successful.
     * @throws FileMakerException
     */
    public function getField($fieldName)
    {
        if (isset($this->fields[$fieldName])) {
            return $this->fields[$fieldName];
        }
        return $this->fm->returnOrThrowException(
            'Field '.$fieldName.' Not Found in Layout '. $this->layout->getName()
        );
    }

    /**
     * Returns an associative array with the names of all fields as keys and
     * Field objects as the array values.
     *
     * @return array Array of {@link Field} objects.
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Loads extended (FMPXMLLAYOUT) layout information.
     *
     * @access private
     *
     * @return boolean|FileMakerException TRUE, if successful.
     * @throws FileMakerException
     */
    public function loadExtendedInfo()
    {
        return $this->fm->returnOrThrowException('Related sets do not support extended information.');
    }
}
