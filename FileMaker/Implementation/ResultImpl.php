<?php
namespace airmoi\FileMaker\Implementation;
class FileMaker_Result_Implementation {

    private $_fm;
    private $_layout;
    private $_records;
    private $_tableCount;
    private $_foundSetCount;
    private $_fetchCount;

    public function __construct( FileMaker_Implementation $fm) {
        $this->_fm = $fm;
    }

    public function getLayout() {
        return $this->_layout;
    }

    public function getRecords() {
        return $this->_records;
    }

    public function getFields() {
        return $this->_layout->listFields();
    }

    public function getRelatedSets() {
        return $this->_layout->listRelatedSets();
    }

    public function getTableRecordCount() {
        return $this->_tableCount;
    }

    public function getFoundSetCount() {
        return $this->_foundSetCount;
    }

    public function getFetchCount() {
        return $this->_fetchCount;
    }

    public function getFirstRecord() {
        return $this->_records[0];
    }

    public function getLastRecord() {
        return $this->_records[sizeof($this->_records) - 1];
    }

}
