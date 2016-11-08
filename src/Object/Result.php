<?php
/**
 * @copyright Copyright (c) 2016 by 1-more-thing (http://1-more-thing.com) All rights reserved.
 * @licence BSD
 */
namespace airmoi\FileMaker\Object;

use airmoi\FileMaker\FileMaker;

/**
 * Result set description class. Contains all the information about a set of
 * records returned by a command.
 *
 * @package FileMaker
 */
class Result
{
    /**
     * @var FileMaker
     */
    public $fm;

    /**
     * @var Layout
     */
    public $layout;
    public $records;
    public $tableCount;
    public $foundSetCount;
    public $fetchCount;

    /**
     * Result object constructor.
     *
     * @param FileMaker $fm FileMaker object
     *        that this result came from.
     */
    public function __construct(FileMaker $fm)
    {
        $this->fm = $fm;
    }

    /**
     * Returns a Layout object that describes the layout of this
     * result set.
     *
     * @return Layout Layout object.
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * Returns an array containing each record in the result set.
     *
     * Each member of the array is a Record object, or an
     * instance of the alternate class you specified to use for records
     * (see {@link Record}. The array may be empty if
     * the result set contains no records.
     *
     * @return Record[] Record objects.
     */
    public function getRecords()
    {
        return $this->records;
    }

    /**
     * Returns a list of the names of all fields in the records in
     * this result set.
     *
     * Only the field names are returned. If you need additional
     * information, examine the Layout object provided by the
     * {@link getLayout()} method.
     *
     * @return array List of field names as strings.
     */
    public function getFields()
    {
        return $this->layout->listFields();
    }

    /**
     * Returns the names of related tables for all portals present in records
     * in this result set.
     *
     * @return array List of related table names as strings.
     */
    public function getRelatedSets()
    {
        return $this->layout->listRelatedSets();
    }

    /**
     * Returns the number of records in the table that was accessed.
     *
     * @return integer Total record count in table.
     */
    public function getTableRecordCount()
    {
        return $this->tableCount;
    }

    /**
     * Returns the number of records in the entire found set.
     *
     * @return integer Found record count.
     */
    public function getFoundSetCount()
    {
        return $this->foundSetCount;
    }

    /**
     * Returns the number of records in the filtered result set.
     *
     * If no range parameters were specified on the Find command,
     * then this value is equal to the result of the {@link getFoundSetCount()}
     * method. It is always equal to the value of
     * count($response->{@link getRecords()}).
     *
     * @return integer Filtered record count.
     */
    public function getFetchCount()
    {
        return $this->fetchCount;
    }

    /**
     * Returns the first record in this result set.
     *
     * @return Record First record.
     */
    public function getFirstRecord()
    {
        return $this->records[0];
    }

    /**
     * Returns the last record in this result set.
     *
     * @return Record Last record.
     */
    public function getLastRecord()
    {
        return $this->records[sizeof($this->records) - 1];
    }
}
