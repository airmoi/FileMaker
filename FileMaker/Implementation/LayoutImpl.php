<?php
namespace airmoi\FileMaker\Implementation;
use airmoi\FileMaker;
use airmoi\FileMaker\Parser;

class FileMaker_Layout_Implementation {
    /**
     *
     * @var FileMaker\FileMaker_Implementation 
     */
    private $_fm;
    private $_name;
    private $_fields = array();
    private $_relatedSets = array();
    private $_valueLists = array();
    private $_valueListTwoFields = array();
    private $_database;
    private $_extended = false;

    function __construct(FileMaker_Implementation $fm) {
        $this->_fm = $fm;
    }

    function getName() {
        return $this->_name;
    }

    function getDatabase() {
        return $this->_database;
    }

    function listFields() {
        return array_keys($this->_fields);
    }

    /**
     * 
     * @param type $fieldName
     * @return FileMaker\FileMaker_Error|FileMaker_Field_Implementation
     */
    function getField($fieldName) {
        if (isset($this->_fields[$fieldName])) {
            return $this->_fields[$fieldName];
        }
        return new FileMaker_Error($this->_fm, 'Field Not Found');
    }

    function getFields() {
        return $this->_fields;
    }

    function listRelatedSets() {
        return array_keys($this->_relatedSets);
    }

    function getRelatedSet($relatedSetName) {
        if (isset($this->_relatedSets[$relatedSetName])) {
            return $this->_relatedSets[$relatedSetName];
        }
        return new FileMaker_Error($this->_fm, 'RelatedSet Not Found');
    }

    function getRelatedSets() {
        return $this->_relatedSets;
    }

    function listValueLists() {
        $ExtendedInfos = $this->loadExtendedInfo();
        if (FileMaker::isError($ExtendedInfos)) {
            return $ExtendedInfos;
        }
        return array_keys($this->_valueLists);
    }

    function getValueListTwoFields($listName, $recordId = null) {

        $ExtendedInfos = $this->loadExtendedInfo($recordId);
        if (FileMaker::isError($ExtendedInfos)) {
            return $ExtendedInfos;
        }
        return isset($this->_valueLists[$listName]) ?
                $this->_valueListTwoFields[$listName] : null;
    }

    function getValueList($listName, $recordId = null) {
        $ExtendedInfos = $this->loadExtendedInfo($recordId);
        if (FileMaker::isError($ExtendedInfos)) {
            return $ExtendedInfos;
        }
        return isset($this->_valueLists[$listName]) ?
                $this->_valueLists[$listName] : null;
    }

    function getValueListsTwoFields($recordId = null) {
        $ExtendedInfos = $this->loadExtendedInfo($recordId);
        if (FileMaker::isError($ExtendedInfos)) {
            return $ExtendedInfos;
        }
        return $this->_valueListTwoFields;
    }

    function getValueLists($RecordId = null) {
        $ExtendedInfos = $this->loadExtendedInfo($RecordId);
        if (FileMaker::isError($ExtendedInfos)) {
            return $ExtendedInfos;
        }
        return $this->_valueLists;
    }

    function loadExtendedInfo($recordId = null) {
        if (!$this->_extended) {

            if ($recordId != null) {
                $result = $this->_fm->_execute(array('-db' => $this->_fm->getProperty('database'),
                    '-lay' => $this->getName(),
                    '-recid' => $recordId,
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
