<?php
/**
 * @copyright Copyright (c) 2016 by 1-more-thing (http://1-more-thing.com) All rights reserved.
 * @license BSD
 */
namespace airmoi\FileMaker\Command;

use airmoi\FileMaker\FileMaker;
use airmoi\FileMaker\FileMakerException;
use airmoi\FileMaker\FileMakerValidationException;
use airmoi\FileMaker\Helpers\DateFormat;

/**
 * Command class that adds a new record.
 * Create this command with {@link FileMaker::newAddCommand()}.
 *
 * @package FileMaker
 */
class Add extends Command
{
    /**
     * Add command constructor.
     *
     * @ignore
     * @param FileMaker $fm FileMaker object the command was created by.
     * @param string $layout Layout to add a record to.
     * @param array $values Associative array of field name => value pairs. To set field repetitions,
     * use a numerically indexed array for the value of a field, with the numeric keys
     * corresponding to the repetition number to set.
     */
    public function __construct(FileMaker $fm, $layout, $values = [])
    {
        parent::__construct($fm, $layout);
        foreach ($values as $fieldname => $value) {
            if (!is_array($value)) {
                $this->setField($fieldname, $value, 0);
            } else {
                foreach ($value as $repetition => $repetitionValue) {
                    $this->setField($fieldname, $repetitionValue, $repetition) ;
                }
            }
        }
    }

    /**
     *
     * @return \airmoi\FileMaker\Object\Result|FileMakerException|FileMakerValidationException
     * @throws FileMakerException
     * @throws FileMakerValidationException
     */
    public function execute()
    {
        if ($this->fm->getProperty('prevalidate')) {
            $validation = $this->validate();
            if (FileMaker::isError($validation)) {
                return $validation;
            }
        }
        $layout = $this->fm->getLayout($this->layout);
        $params = $this->getCommandParams();
        $params['-new'] = true;
        foreach ($this->fields as $field => $values) {
            if (strpos($field, '.') !== false) {
                list($fieldname, $fieldType) = explode('.', $field, 2);
                $fieldType = '.' . $fieldType;
            } else {
                $fieldname = $field;
                $fieldInfos = $layout->getField($field);
                if ($fieldInfos->isGlobal()) {
                    $fieldType = '.global';
                } else {
                    $fieldType = '';
                }
            }
            foreach ($values as $repetition => $value) {
                $params[$fieldname . '(' . ($repetition + 1) . ')' . $fieldType] = $value;
            }
        }
        $result = $this->fm->execute($params);
        return $this->getResult($result);
    }

    /**
     * Sets the new value for a field.
     *
     * @param string $field Name of field to set.
     * @param string $value Value to set for this field.
     * @param integer $repetition Field repetition number to set,
     *        Defaults to the first repetition.
     *
     * @return string
     */
    public function setField($field, $value, $repetition = 0)
    {
        $fieldInfos = $this->fm->getLayout($this->layout)->getField($field);
        /* if(FileMaker::isError($fieldInfos)){
            return $fieldInfos;
        }*/

        $format = FileMaker::isError($fieldInfos) ? null : $fieldInfos->result;
        if ($format === 'date' || $format === 'timestamp') {
            $dateFormat = $this->fm->getProperty('dateFormat');
            try {
                if ($format === 'date') {
                    $value = DateFormat::convert($value, $dateFormat, 'm/d/Y');
                } else {
                    $value = DateFormat::convert($value, $dateFormat . ' H:i:s', 'm/d/Y H:i:s');
                }
            } catch (\Exception $e) {
                return $this->fm->returnOrThrowException(
                    $value . ' could not be converted to a valid timestamp for field '
                    . $field . ' (expected format '. $dateFormat .')'
                );
            }
        }

        $this->fields[$field][$repetition] = $value;
        return $value;
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
     *
     * @return string|FileMakerException
     * @throws FileMakerException
     */
    public function setFieldFromTimestamp($field, $timestamp, $repetition = 0)
    {
        $layout = $this->fm->getLayout($this->layout);
        $fieldInfos = $layout->getField($field);
        switch ($fieldInfos->getResult()) {
            case 'date':
                return $this->setField($field, date('m/d/Y', $timestamp), $repetition);
            case 'time':
                return $this->setField($field, date('H:i:s', $timestamp), $repetition);
            case 'timestamp':
                return $this->setField($field, date('m/d/Y H:i:s', $timestamp), $repetition);
        }

        return $this->fm->returnOrThrowException(
            'Only time, date, and timestamp fields can be set to the value of a timestamp.'
        );
    }
}
