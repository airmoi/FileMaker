<?php

namespace airmoi\FileMaker\Command;

use airmoi\FileMaker\FileMaker;
use airmoi\FileMaker\FileMakerException;

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
 * Command class that adds a new record.
 * Create this command with {@link FileMaker::newAddCommand()}.
 *
 * @package FileMaker
 */
class Add extends Command {

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
    public function __construct(FileMaker $fm, $layout, $values = array()) {
        parent::__construct($fm, $layout);
        foreach ($values as $fieldname => $value) {
            if (!is_array($value)) {
                $this->setField($fieldname, $value, 0);
            }
            else {
                foreach ( $value as $repetition => $repetitionValue ){
                    $this->setField($fieldname, $repetitionValue, $repetition) ;
                }
            }
        }
    }

    /**
     *
     * @return \airmoi\FileMaker\Object\Result|FileMakerException
     * @throws FileMakerException
     */
    public function execute() {
        if ($this->fm->getProperty('prevalidate')) {
            $validation = $this->validate();
        }
        $layout = $this->fm->getLayout($this->_layout);
        $params = $this->_getCommandParams();
        $params['-new'] = true;
        foreach ($this->_fields as $field => $values) {
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
        return $this->_getResult($result);
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
    public function setField($field, $value, $repetition = 0) {
        //handle related fields in portals or in model
        if($pos = strpos($field, ':')){
            $fieldName = substr($field, 0, strpos($field, '.'));
            $relationName = substr($field, 0, $pos);
            if( $this->fm->getLayout($this->_layout)->hasRelatedSet($relationName)) {
                $Field = $this->fm->getLayout($this->_layout)->getRelatedSet(substr($field, 0, $pos))->getField($fieldName);
            }
            else {
                $Field = $this->fm->getLayout($this->_layout)->getField($field);
            }
        }
        else {
            $Field = $this->fm->getLayout($this->_layout)->getField($field);
        }
        /*if ( array_search($field, $this->fm->getLayout($this->_layout)->listFields()) === false){
                throw new FileMakerException($this->fm, 'Field "'.$field.'" is missing');
        }*/

        $format = $Field->result;
        if( !empty($value) && $this->fm->getProperty('dateFormat') !== null && ($format == 'date' || $format == 'timestamp')){
            if( $format == 'date' ){
                $dateTime = \DateTime::createFromFormat($this->fm->getProperty('dateFormat') . ' H:i:s', $value . ' 00:00:00');
                $value = $dateTime->format('m/d/Y');
            } else {
                $dateTime = \DateTime::createFromFormat($this->fm->getProperty('dateFormat') . ' H:i:s', $value );
                $value = $dateTime->format( 'm/d/Y H:i:s' );
            }
        }

        $this->_fields[$field][$repetition] = $value;
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
    public function setFieldFromTimestamp($field, $timestamp, $repetition = 0) {
        $layout = $this->fm->getLayout($this->_layout);
        $fieldInfos = $layout->getField($field);
        switch ($fieldInfos->getResult()) {
            case 'date':
                return $this->setField($field, date('m/d/Y', $timestamp), $repetition);
            case 'time':
                return $this->setField($field, date('H:i:s', $timestamp), $repetition);
            case 'timestamp':
                return $this->setField($field, date('m/d/Y H:i:s', $timestamp), $repetition);
        }
        $error = new FileMakerException($this->fm, 'Only time, date, and timestamp fields can be set to the value of a timestamp.');
        if($this->fm->getProperty('errorHandling') == 'default') {
            return $error;
        }
        throw $error;
    }

}
