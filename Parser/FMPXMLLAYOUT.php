<?php

namespace airmoi\FileMaker\Parsers;


class FMPXMLLAYOUT {

    private $_fields;
    private $_valueLists;
    private $_valueListTwoFields;
    private $_fm;
    private $_xmlParser;
    private $_isParsed = false;
    private $_fieldName;
    private $_valueList;
    private $_displayValue;

    public function __construct($fm) {
        $this->_fm = $fm;
    }

    public function parse($xmlResponse) {
        if (empty($xmlResponse)) {
            return new FileMaker_Error($this->_fm, 'Did not receive an XML document from the server.');
        }
        $this->_xmlParser = xml_parser_create();
        xml_set_object($this->_xmlParser, $this);
        xml_parser_set_option($this->_xmlParser, XML_OPTION_CASE_FOLDING, false);
        xml_parser_set_option($this->_xmlParser, XML_OPTION_TARGET_ENCODING, 'UTF-8');
        xml_set_element_handler($this->_xmlParser, '_start', '_end');
        xml_set_character_data_handler($this->_xmlParser, '_cdata');
        if (!@xml_parse($this->_xmlParser, $xmlResponse)) {
            return new FileMaker_Error(sprintf('XML error: %s at line %d', xml_error_string(xml_get_error_code($this->_xmlParser)), xml_get_current_line_number($this->_xmlParser)));
        }
        xml_parser_free($this->_xmlParser);
        if (!empty($this->errorCode)) {
            return new FileMaker\FileMaker_Error($this->_fm, null, $this->errorCode);
        }
        $this->_isParsed = true;
        return true;
    }

    public function setExtendedInfo(Impl\FileMaker_Layout_Implementation $layout) {
        if (!$this->_isParsed) {
            return new FileMaker_Error($this->_fm, 'Attempt to set extended information before parsing data.');
        }
        $layout->_valueLists = $this->_valueLists;
        $layout->_valueListTwoFields = $this->_valueListTwoFields;
        foreach ($this->_fields as $fieldName => $fieldInfos) {
            $field = $layout->getField($fieldName);
            $field->_impl->_styleType = $fieldInfos['styleType'];
            $field->_impl->_valueList = $fieldInfos['valueList'] ? $fieldInfos['valueList'] : null;
        }
    }

    private function _start($unusedVar, $type, $datas) {
        $datas = $this->_fm->toOutputCharset($datas);
        switch ($type) {
            case 'FIELD':
                $this->_fieldName = $datas['NAME'];
                break;
            case 'STYLE':
                $this->_fields[$this->_fieldName]['styleType'] = $datas['TYPE'];
                $this->_fields[$this->_fieldName]['valueList'] = $datas['VALUELIST'];
                break;
            case 'VALUELIST':
                $this->_valueLists[$datas['NAME']] = array();
                $this->_valueListTwoFields[$datas['NAME']] = array();
                $this->_valueList = $datas['NAME'];
                break;
            case 'VALUE':
                $this->_displayValue = $datas['DISPLAY'];
                $this->_valueLists[$this->_valueList][] = '';
                break;
        }
        $this->inside_data = false;
    }

    private function _end($unusedVar, $type) {
        switch ($type) {
            case 'FIELD':
                $this->_fieldName = null;
                break;
            case 'VALUELIST':
                $this->_valueList = null;
                break;
        }

        $this->inside_data = false;
    }

    public function _cdata($unusedVar, $datas) {
        if ($this->_valueList !== null && preg_match('|\S|', $datas)) {

            if ($this->inside_data) {
                $value = $this->_valueListTwoFields[$this->_valueList][$this->_displayValue];
                $datas = $value . $datas;
            }
            $arrayVal = array($this->_displayValue => $this->_fm->toOutputCharset($datas));
            $this->associative_array_push($this->_valueListTwoFields[$this->_valueList], $arrayVal);
            $this->_valueLists[$this->_valueList][count($this->_valueLists[$this->_valueList]) - 1] .= $this->_fm->toOutputCharset($datas);
            $this->inside_data = true;
        }
    }

    public function associative_array_push($array, $values) {
        if (is_array($values)) {
            foreach ($values as $key => $value) {
                $array[$key] = $value;
            }
            return $array;
        }
        return false;
    }

}
