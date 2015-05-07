<?php

namespace airmoi\FileMaker\Implementation;

class FileMaker_RelatedSet_Implementation {

    private $_layout;
    private $_fm;
    private $_name;
    private $_fields = array();

    public function __construct(FileMaker_Layout_Implementation $layout) {
        $this->_layout = $layout;
        $this->_fm = $layout->_impl->fm;
    }

    public function getName() {
        return $this->_name;
    }

    public function listFields() {
        return array_keys($this->_fields);
    }

    public function getField($fieldName) {
        if (isset($this->_fields[$fieldName])) {
            return $this->_fields[$fieldName];
        }
        return new FileMaker_Error($this->_fm, 'Field Not Found');
    }

    public function getFields() {
        return $this->_fields;
    }

    public function loadExtendedInfo() {
        return new FileMaker_Error($this->_fm, 'Related sets do not support extended information.');
    }

}
