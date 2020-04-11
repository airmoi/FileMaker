<?php
/**
 * @copyright Copyright (c) 2016 by 1-more-thing (http://1-more-thing.com) All rights reserved.
 * @license BSD
 */
namespace airmoi\FileMaker\Parser;

use airmoi\FileMaker\FileMaker;
use airmoi\FileMaker\FileMakerException;
use airmoi\FileMaker\Object\Layout;

/**
 * Class used to parse FMPXMLLAYOUT structure
 *
 * @package FileMaker
 */
class FMPXMLLAYOUT
{
    /**
     * @var FileMaker
     */
    private $fm;

    private $fields = [];
    private $valueLists;
    private $valueListTwoFields;
    private $xmlParser;
    private $isParsed = false;
    private $fieldName;
    private $valueList;
    private $displayValue;
    private $insideData;

    /**
     *
     * @param FileMaker $fm
     */
    public function __construct(FileMaker $fm)
    {
        $this->fm = $fm;
    }

    /**
     *
     * @param string $xmlResponse
     * @return boolean|FileMakerException
     * @throws FileMakerException
     */
    public function parse($xmlResponse)
    {
        if (empty($xmlResponse)) {
            return $this->fm->returnOrThrowException('Did not receive an XML document from the server.');
        }
        $this->xmlParser = xml_parser_create();
        xml_set_object($this->xmlParser, $this);
        xml_parser_set_option($this->xmlParser, XML_OPTION_CASE_FOLDING, false);
        xml_parser_set_option($this->xmlParser, XML_OPTION_TARGET_ENCODING, 'UTF-8');
        /** @psalm-suppress UndefinedFunction */
        xml_set_element_handler($this->xmlParser, 'start', 'end');
        /** @psalm-suppress UndefinedFunction */
        xml_set_character_data_handler($this->xmlParser, 'cdata');
        if (!@xml_parse($this->xmlParser, $xmlResponse)) {
            return $this->fm->returnOrThrowException(
                sprintf(
                    'XML error: %s at line %d',
                    xml_error_string(xml_get_error_code($this->xmlParser)),
                    xml_get_current_line_number($this->xmlParser)
                )
            );
        }
        xml_parser_free($this->xmlParser);
        if (!empty($this->errorCode)) {
            return $this->fm->returnOrThrowException(null, $this->errorCode);
        }
        $this->isParsed = true;
        return true;
    }

    /**
     * Add extended infos to a Layout object
     *
     * @param Layout $layout
     * @return FileMakerException
     * @throws FileMakerException
     */
    public function setExtendedInfo(Layout $layout)
    {
        if (!$this->isParsed) {
            return $this->fm->returnOrThrowException('Attempt to set extended information before parsing data.');
        }
        $layout->valueLists = $this->valueLists;
        $layout->valueListTwoFields = $this->valueListTwoFields;
        foreach ($this->fields as $fieldName => $fieldInfos) {
            try {
                $field = $layout->getField($fieldName);
                if (!FileMaker::isError($field)) {
                    $field->styleType = $fieldInfos['styleType'];
                    $field->valueList = $fieldInfos['valueList'] ? $fieldInfos['valueList'] : null;
                }
            } catch (\Exception $e) {
                //Field may be missing when it is stored in a portal, ommit error
            }
        }
        return true;
    }

    /**
     * xml_parser start element handler
     *
     * @param resource $parser
     * @param string $type
     * @param array $datas
     */
    private function start($parser, $type, $datas)
    {
        $datas = $this->fm->toOutputCharset($datas);
        switch ($type) {
            case 'FIELD':
                $this->fieldName = $datas['NAME'];
                break;
            case 'STYLE':
                $this->fields[$this->fieldName]['styleType'] = $datas['TYPE'];
                $this->fields[$this->fieldName]['valueList'] = $datas['VALUELIST'];
                break;
            case 'VALUELIST':
                $this->valueLists[$datas['NAME']] = [];
                $this->valueListTwoFields[$datas['NAME']] = [];
                $this->valueList = $datas['NAME'];
                break;
            case 'VALUE':
                $this->displayValue = $datas['DISPLAY'];
                $this->valueLists[$this->valueList][] = '';
                break;
        }
        $this->insideData = false;
    }

    /**
     * xml_parser end element handler
     *
     * @param resource $parser
     * @param string $type
     */
    private function end($parser, $type)
    {
        switch ($type) {
            case 'FIELD':
                $this->fieldName = null;
                break;
            case 'VALUELIST':
                $this->valueList = null;
                break;
        }

        $this->insideData = false;
    }

    /**
     * xml_parser character data handler (cdata)
     *
     * @param resource $parser
     * @param string $datas
     */
    public function cdata($parser, $datas)
    {
        if ($this->valueList !== null && preg_match('|\S|', $datas)) {
            if ($this->insideData) {
                $value = $this->valueListTwoFields[$this->valueList][$this->displayValue];
                $datas = $value . $datas;
            }
            $arrayVal = [$this->displayValue => $this->fm->toOutputCharset($datas)];
            $this->associativeArrayPush($this->valueListTwoFields[$this->valueList], $arrayVal);
            $valueListNum = count($this->valueLists[$this->valueList]) - 1;
            $this->valueLists[$this->valueList][$valueListNum] .= $this->fm->toOutputCharset($datas);
            $this->insideData = true;
        }
    }

    /**
     * Add values to an existing array
     *
     * @param array $array
     * @param array $values
     * @return boolean
     */
    public function associativeArrayPush(&$array, $values)
    {
        if (is_array($values)) {
            foreach ($values as $key => $value) {
                $array[$key] = $value;
            }
            return $array;
        }
        return false;
    }
}
