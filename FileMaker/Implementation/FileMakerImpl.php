<?php

namespace airmoi\FileMaker\Implementation;

use airmoi\FileMaker\FileMaker;
use airmoi\FileMaker\Parser\FileMaker_Parser_FMResultSet;
use airmoi\FileMaker\Parser\FileMaker_Parser_FMPXMLLAYOUT;

require_once(dirname(__FILE__).'/parser/FMPXMLLAYOUT.php');
require_once(dirname(__FILE__).'/parser/FMResultSet.php');

class FileMaker_Implementation {

    private $_properties = array('charset' => 'utf-8');
    private $_logger = null;
    private static $_layouts = [];

    //var $V9a3dcbce; Unused ??

    public function getAPIVersion() {
        return '1.1';
    }

    public static function getMinServerVersion() {
        return '10.0.0.0';
    }

    public function __construct($database, $host, $username, $password) {
        $V07cc694b = time();
        if ((@include dirname(__FILE__) . '/../conf/filemaker-api.php') && isset($__FM_CONFIG)) {
            foreach ($__FM_CONFIG as $property => $value) {
                $this->setProperty($property, $value);
            }
        }
        if (!is_null($host)) {
            $this->setProperty('hostspec', $host);
        }
        if (!is_null($database)) {
            $this->setProperty('database', $database);
        }
        if (!is_null($username)) {
            $this->setProperty('username', $username);
        }
        if (!is_null($password)) {
            $this->setProperty('password', $password);
        }
    }

    public function setProperty($property, $value) {
        $this->_properties[$property] = $value;
    }

    public function getProperty($property) {
        return isset($this->_properties[$property]) ? $this->_properties[$property] : null;
    }

    public function getProperties() {
        return $this->_properties;
    }

    public function setLogger($logger) {
        if (!is_a($logger, 'Log')) {
            return new FileMaker_Error($this, 'setLogger() must be passed an instance of PEAR::Log');
        }
        $this->_logger = & $logger;
    }

    public function log($message, $level) {
        if ($this->_logger === null) {
            return;
        }
        $logLevel = $this->getProperty('logLevel');
        if ($logLevel === null || $level > $logLevel) {
            return;
        }
        switch ($level) {
            case FileMaker::LOG_DEBUG:
                $this->_logger->log($message, PEAR_LOG_DEBUG);
                break;
            case FileMaker::LOG_INFO:
                $this->_logger->log($message, PEAR_LOG_INFO);
                break;
            case FileMaker::LOG_ERR:
                $this->_logger->log($message, PEAR_LOG_ERR);
                break;
        }
    }

    public function toOutputCharset($value) {
        if (strtolower($this->getProperty('charset')) != 'iso-8859-1') {
            return $value;
        }
        if (is_array($value)) {
            $output = array();
            foreach ($value as $key => $value) {
                $output[$this->toOutputCharset($key)] = $this->toOutputCharset($value);
            }
            return $output;
        }
        if (!is_string($value)) {
            return $value;
        }
        return utf8_decode($value);
    }

    public function newAddCommand($layout, $values = array()) {
        return new FileMaker_Command_Add($this, $layout, $values);
    }

    public function newEditCommand($layout, $recordId, $updatedValues = array()) {
        return new FileMaker_Command_Edit($this, $layout, $recordId, $updatedValues);
    }

    public function newDeleteCommand($layout, $recordId) {
        return new FileMaker_Command_Delete($this, $layout, $recordId);
    }

    public function newDuplicateCommand($layout, $recordId) {
        return new FileMaker_Command_Duplicate($this, $layout, $recordId);
    }

    public function newFindCommand($layout) {
        return new FileMaker_Command_Find($this, $layout);
    }

    public function newCompoundFindCommand($layout) {
        return new FileMaker_Command_CompoundFind($this, $layout);
    }

    public function newFindRequest($layout) {
        return new FileMaker_Command_FindRequest($this, $layout);
    }

    public function newFindAnyCommand($layout) {
        return new FileMaker_Command_FindAny($this, $layout);
    }

    public function newFindAllCommand($layout) {
        return new FileMaker_Command_FindAll($this, $layout);
    }

    public function newPerformScriptCommand($layout, $scriptName, $scriptParameter = null) {
        return new FileMaker_Command_PerformScript($this, $layout, $scriptName, $scriptParameter);
    }

    public function createRecord($layoutName, $fieldValues = array()) {
        $layout = $this->getLayout($layoutName);
        if (FileMaker::isError($layout)) {
            return $layout;
        }
        $record = new $this->V73ee434e['recordClass']($layout);
        if (is_array($fieldValues)) {
            foreach ($fieldValues as $fieldName => $fieldValue) {
                if (is_array($fieldValue)) {
                    foreach ($fieldValue as $repetition => $value) {
                        $record->setField($fieldName, $value, $repetition);
                    }
                } else {
                    $record->setField($fieldName, $fieldValue);
                }
            }
        }
        return $record;
    }

    public function getRecordById($layout, $recordId) {
        $request = $this->newFindCommand($layout);
        $request->setRecordId($recordId);
        $result = $request->execute();
        if (FileMaker::isError($result)) {
            return $result;
        }
        $record = $result->getRecords();
        if (!$record) {
            return new FileMaker_Error($this, 'Record . ' . $recordId . ' not found in layout "' . $layout . '".');
        }
        return $record[0];
    }

    /**
     * Returns the FileMaker_Layout object
     * @param string $layoutName
     * @return FileMaker_Layout|FileMaker_Error
     */
    public function getLayout($layoutName) {
        static $_layouts = array();
        if (isset(self::$_layouts[$layoutName])) {
            return self::$_layouts[$layoutName];
        }
        $request = $this->_execute(array('-db' => $this->getProperty('database'),
            '-lay' => $layoutName,
            '-view' => true));
        if (FileMaker::isError($request)) {
            return $request;
        }
        $parser = new FileMaker_Parser_FMResultSet($this);
        $result = $parser->parse($request);
        if (FileMaker::isError($result)) {
            return $result;
        }
        $layout = new FileMaker_Layout($this);
        $result = $parser->setLayout($layout);
        if (FileMaker::isError($result)) {
            return $result;
        }
        self::$_layouts[$layoutName] = $layout;
        return $layout;
    }

    public function listDatabases() {
        $request = $this->_execute(array('-dbnames' => true));
        if (FileMaker::isError($request)) {
            return $request;
        }
        $parser = new FileMaker_Parser_fmresultset($this);
        $result = $parser->parse($request);
        if (FileMaker::isError($result)) {
            return $result;
        }
        $list = array();
        foreach ($parser->parsedResult as $data) {
            $list[] = $data['fields']['DATABASE_NAME'][0];
        }
        return $list;
    }

    public function listScripts() {
        $request = $this->_execute(array('-db' => $this->getProperty('database'),
            '-scriptnames' => true));
        if (FileMaker::isError($request)) {
            return $request;
        }
        $parser = new FileMaker_Parser_FMResultSet($this);
        $result = $parser->parse($request);
        if (FileMaker::isError($result)) {
            return $result;
        }
        $list = array();
        foreach ($parser->parsedResult as $data) {
            $list[] = $data['fields']['SCRIPT_NAME'][0];
        }
        return $list;
    }

    public function listLayouts() {
        $request = $this->_execute(array('-db' => $this->getProperty('database'),
            '-layoutnames' => true));
        if (FileMaker::isError($request)) {
            return $request;
        }
        $parser = new FileMaker_Parser_FMResultSet($this);
        $result = $parser->parse($request);
        if (FileMaker::isError($result)) {
            return $result;
        }
        $list = array();
        foreach ($parser->parsedResult as $data) {
            $list[] = $data['fields']['LAYOUT_NAME'][0];
        }
        return $list;
    }

    public function getContainerData($url) {
        if (!function_exists('curl_init')) {
            return new FileMaker_Error($this, 'cURL is required to use the FileMaker API.');
        }
        if (strncasecmp($url, '/fmi/xml/cnt', 11) != 0) {
            return new FileMaker_Error($this, 'getContainerData() does not support remote containers');
        } else {
            $hostspec = $this->getProperty('hostspec');
            if (substr($hostspec, -1, 1) == '/') {
                $hostspec = substr($hostspec, 0, -1);
            }
            $hostspec .= $url;
            $hostspec = htmlspecialchars_decode($hostspec);
            $hostspec = str_replace(" ", "%20", $hostspec);
        }
        $this->log('Request for ' . $hostspec, FILEMAKER_LOG_INFO);
        $curl = curl_init($hostspec);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FAILONERROR, true);
        $isHeadersSent = FALSE;
        if (!headers_sent()) {
            $isHeadersSent = TRUE;
            curl_setopt($curl, CURLOPT_HEADER, true);
        }
        $this->_setCurlWPCSessionCookie($curl);

        if ($this->getProperty('username')) {
            $authString = base64_encode($this->getProperty('username') . ':' . $this->getProperty('password'));
            $headers = array('Authorization: Basic ' . $authString, 'X-FMI-PE-ExtendedPrivilege: IrG6U+Rx0F5bLIQCUb9gOw==');
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        } else {
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-FMI-PE-ExtendedPrivilege: IrG6U+Rx0F5bLIQCUb9gOw=='));
        }
        if ($curlOptions = $this->getProperty('curlOptions')) {
            foreach ($curlOptions as $property => $value) {
                curl_setopt($curl, $property, $value);
            }
        }
        $curlResponse = curl_exec($curl);
        $this->_setClientWPCSessionCookie($curlResponse);
        if ($isHeadersSent) {
            $curlResponse = $this->_eliminateContainerHeader($curlResponse);
        }
        $this->log($curlResponse, FileMaker::LOG_DEBUG);
        if ($curlError = curl_errno($curl)) {
            return new FileMaker_Error($this, 'Communication Error: (' . $curlError . ') ' . curl_error($curl));
        }
        curl_close($curl);
        return $curlResponse;
    }

    private function _execute($params, $grammar = 'fmresultset') {
        if (!function_exists('curl_init')) {
            return new FileMaker_Error($this, 'cURL is required to use the FileMaker API.');
        }
        $RESTparams = array();
        foreach ($params as $option => $value) {
            if (strtolower($this->getProperty('charset')) != 'utf-8' && $value !== true) {
                $value = utf8_encode($value);
            }
            $RESTparams[] = urlencode($option) . ($value === true ? '' : '=' . urlencode($value));
        }
        $host = $this->getProperty('hostspec');
        if (substr($host, -1, 1) != '/') {
            $host .= '/';
        }
        $host .= 'fmi/xml/' . $grammar . '.xml';
        $this->log('Request for ' . $host, FileMaker::LOG_INFO);
        $curl = curl_init($host);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FAILONERROR, true);
        $curlHeadersSent = FALSE;
        if (!headers_sent()) {
            $curlHeadersSent = TRUE;
            curl_setopt($curl, CURLOPT_HEADER, true);
        }
        $this->_setCurlWPCSessionCookie($curl);

        if ($this->getProperty('username')) {
            $auth = base64_encode(utf8_decode($this->getProperty('username')) . ':' . utf8_decode($this->getProperty('password')));
            $authHeader = 'Authorization: Basic ' . $auth;
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded; charset=utf-8', 'X-FMI-PE-ExtendedPrivilege: IrG6U+Rx0F5bLIQCUb9gOw==', $authHeader));
        } else {
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded; charset=utf-8', 'X-FMI-PE-ExtendedPrivilege: IrG6U+Rx0F5bLIQCUb9gOw=='));
        }

        curl_setopt($curl, CURLOPT_POSTFIELDS, implode('&', $RESTparams));
        if ($curlOptions = $this->getProperty('curlOptions')) {
            foreach ($curlOptions as $key => $value) {
                curl_setopt($curl, $key, $value);
            }
        }
        $curlResponse = curl_exec($curl);
        $this->_setClientWPCSessionCookie($curlResponse);
        if ($curlHeadersSent) {
            $curlResponse = $this->_eliminateXMLHeader($curlResponse);
        }
        $this->log($curlResponse, FileMaker::LOG_DEBUG);
        if ($curlError = curl_errno($curl)) {

            if ($curlError == 52) {
                return new FileMaker_Error($this, 'Communication Error: (' . $curlError . ') ' . curl_error($curl) . ' - The Web Publishing Core and/or FileMaker Server services are not running.', $curlError);
            } else if ($curlError == 22) {
                if (stristr("50", curl_error($curl))) {
                    return new FileMaker_Error($this, 'Communication Error: (' . $curlError . ') ' . curl_error($curl) . ' - The Web Publishing Core and/or FileMaker Server services are not running.', $curlError);
                } else {
                    return new FileMaker_Error($this, 'Communication Error: (' . $curlError . ') ' . curl_error($curl) . ' - This can be due to an invalid username or password, or if the FMPHP privilege is not enabled for that user.', $curlError);
                }
            } else {
                return new FileMaker_Error($this, 'Communication Error: (' . $curlError . ') ' . curl_error($curl), $curlError);
            }
        }
        curl_close($curl);

        return $curlResponse;
    }

    public function getContainerDataURL($url) {
        if (strncasecmp($url, '/fmi/xml/cnt', 11) != 0) {
            $decodedUrl = htmlspecialchars_decode($url);
        } else {
            $decodedUrl = $this->getProperty('hostspec');
            if (substr($decodedUrl, -1, 1) == '/') {
                $decodedUrl = substr($decodedUrl, 0, -1);
            }
            $decodedUrl .= $url;
            $decodedUrl = htmlspecialchars_decode($decodedUrl);
        }
        return $decodedUrl;
    }

    private function _setCurlWPCSessionCookie($curlResponse) {
        if (isset($_COOKIE["WPCSessionID"])) {
            $WPCSessionID = $_COOKIE["WPCSessionID"];
            if (!is_null($WPCSessionID)) {
                $header = "WPCSessionID=" . $WPCSessionID;
                curl_setopt($curlResponse, CURLOPT_COOKIE, $header);
            }
        }
    }

    private function _setClientWPCSessionCookie($curlResponse) {
        $found = preg_match('/WPCSessionID=(\d+?);/m', $curlResponse, $matches);
        if ($found) {
            setcookie("WPCSessionID", $matches[1]);
        }
    }

    private function _getContentLength($curlResponse) {
        $found = preg_match('/Content-Length: (\d+)/', $curlResponse, $matches);
        if ($found) {
            return $matches[1];
        } else {
            return -1;
        }
    }

    private function _eliminateXMLHeader($curlResponse) {
        $isXml = strpos($curlResponse, "<?xml");
        if ($isXml !== false) {
            return substr($curlResponse, $isXml);
        } else {
            return $curlResponse;
        }
    }

    private function _eliminateContainerHeader($curlResponse) {
        $len = strlen("\r\n\r\n");
        $pos = strpos($curlResponse, "\r\n\r\n");
        if ($pos !== false) {
            return substr($curlResponse, $pos + $len);
        } else {
            return $curlResponse;
        }
    }

}
