<?php

namespace airmoi\FileMaker;
use airmoi\FileMaker\Parser\FMResultSet;

/**
 * Simple autoloader that follow the PHP Standards Recommendation #0 (PSR-0)
 * @see https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md for more informations.
 *
 * Code inspired from the SplClassLoader RFC
 * @see https://wiki.php.net/rfc/splclassloader#example_implementation
 */
spl_autoload_register(function ($className) {
    $className = ltrim($className, '\\');
    $fileName = '';
    $namespace = '';
    if ($lastNsPos = strripos($className, '\\')) {
        $namespace = explode( '\\', substr($className, 0, $lastNsPos) );
        array_shift($namespace);
        array_shift($namespace);
        $className = substr($className, $lastNsPos + 1);
        $fileName = str_replace('\\', DIRECTORY_SEPARATOR, implode('\\' , $namespace)) . DIRECTORY_SEPARATOR;
    }
    $fileName = __DIR__ . DIRECTORY_SEPARATOR . $fileName . $className . '.php';
    if (file_exists($fileName)) {
        require $fileName;

        return true;
    }

    return false;
});
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
 * Base FileMaker class. Defines database properties, connects to a database, 
 * and gets information about the API.
 *
 * @package FileMaker
 */
class FileMaker {

    private $_properties = [
        'charset' => 'utf-8',
        'locale' => 'en',
        'logLevel' => 3,
        'hostspec' => 'http://127.0.0.1',
        'database' => '',
        'username' => '',
        'password' => '',
        'recordClass' => 'Record',
        'prevalidate' => false,
        'curlOptions' => [CURLOPT_SSL_VERIFYPEER => false],
    ];
    private $_logger = null;
    private static $_layouts = [];

    /*
     * Find constants.
     */
    const FIND_LT = '<';
    const FIND_LTE = '<=';
    const FIND_GT = '>';
    const FIND_GTE = '>=';
    const FIND_RANGE = '...';
    const FIND_DUPLICATES = '!';
    const FIND_TODAY = '//';
    const FIND_INVALID_DATETIME = '?';
    const FIND_CHAR = '@';
    const FIND_DIGIT = '#';
    const FIND_CHAR_WILDCARD = '*';
    const FIND_LITERAL = '""';
    const FIND_RELAXED = '~';
    const FIND_FIELDMATCH = '==';

    /**
     * Find logical operator constants.
     * Use with the {@link FileMaker_Command_Find::setLogicalOperator()}  
     * method.
     */
    const FIND_AND = 'and';
    const FIND_OR = 'or';

    /**
     * Pre-validation rule constants.
     */
    const RULE_NOTEMPTY = 1;
    const RULE_NUMERICONLY = 2;
    const RULE_MAXCHARACTERS = 3;
    const RULE_FOURDIGITYEAR = 4;
    const RULE_TIMEOFDAY = 5;
    const RULE_TIMESTAMP_FIELD = 6;
    const RULE_DATE_FIELD = 7;
    const RULE_TIME_FIELD = 8;

    /**
     * Sort direction constants. 
     * Use with the {@link FileMaker_Command_Find::addSortRule()} and
     * {@link FileMaker_Command_CompoundFind::addSortRule()} methods.
     */
    const SORT_ASCEND = 'ascend';
    const SORT_DESCEND = 'descend';

    /**
     * Logging level constants.
     */
    const LOG_ERR = 3;
    const LOG_INFO = 6;
    const LOG_DEBUG = 7;

    
    /**
     * Tests whether a variable is a FileMaker API Error.
     *
     * @param mixed $variable Variable to test.
     * @return boolean TRUE, if the variable is a {@link FileMaker_Error} object.
     * @const
     *
     */
    public static function isError($variable) {
        return is_a($variable, 'FileMaker_Error');
    }

    /**
     * Returns the version of the FileMaker API for PHP.
     *
     * @return string API version.
     * @const
     */
    public function getAPIVersion() {
        return '1.1';
    }

    /**
     * Returns the minimum version of FileMaker Server that this API works with.
     *
     * @return string Minimum FileMaker Server version.
     * @const
     */
    public static function getMinServerVersion() {
        return '10.0.0.0';
    }

    /**
     * FileMaker object constructor. 
     * 
     * If you want to use the constructor without specifying all the 
     *  parameters, pass in NULL for the parameters you want to omit. 
     * For example, to specify only the database name, username, and 
     * password, but omit the hostspec, call the constructor as follows:
     *  
     * <samp>
     * new FileMaker('DatabaseName', NULL, 'username', 'password');
     * </samp>
     * 
     * @param string $database Name of the database to connect to.
     * @param string $hostspec Hostspec of web server in FileMaker Server 
     *        deployment. Defaults to http://localhost, if set to NULL.
     * @param string $username Account name to log into database.
     * @param string $password Password for account.
     */
    public function __construct($database = NULL, $hostspec = NULL, $username = NULL, $password = NULL) {
        if (!is_null($hostspec)) {
            $this->setProperty('hostspec', $hostspec);
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

    /**
     * Sets a property to a new value for all API calls.
     *
     * @param string $prop Name of the property to set.
     * @param string $value Property's new value.
     */
    public function setProperty($prop, $value) {
        $this->_properties[$prop] = $value;
    }

    /**
     * Returns the current value of a property.
     *
     * @param string $prop Name of the property.
     *
     * @return string Property's current value.
     */
    public function getProperty($prop) {
        return isset($this->_properties[$prop]) ? $this->_properties[$prop] : null;
    }

    /**
     * Returns an associative array of property name => property value for
     * all current properties and their current values. 
     *
     * This array enables PHP object introspection and debugging when necessary.
     *
     * @return array All current properties.
     */
    public function getProperties() {
        return $this->_properties;
    }

    /**
     * Associates a PEAR Log object with the API for logging requests
     * and responses.
     *
     * @param Log &$logger PEAR Log object.
     */
    public function setLogger($logger) {
        /**
         * @todo handle generic logger ?
         */
        if (!is_a($logger, 'Log')) {
            throw new FileMakerException($this, 'setLogger() must be passed an instance of PEAR::Log');
        }
        $this->_logger = $logger;
    }

    /**
     * Creates a new FileMaker_Command_Add object.
     *
     * @param string $layout Layout to add a record to.
     * @param array $values Associative array of field name => value pairs. 
     *        To set field repetitions, use a numerically indexed array for 
     *        the value of a field, with the numeric keys corresponding to the 
     *        repetition number to set.
     *
     * @return Command\Add New Add command object.
     */
    public function newAddCommand($layout, $values = array()) {
        return new Command\Add($this, $layout, $values);
    }

    /**
     * Creates a new FileMaker_Command_Edit object.
     *
     * @param string $layout Layout that the record is part of.
     * @param string $recordId ID of the record to edit.
     * @param array $updatedValues Associative array of field name => value 
     *        pairs that contain the updated field values. To set field 
     *        repetitions, use a numerically indexed array for the value of a 
     *        field, with the numeric keys corresponding to the repetition 
     *        number to set.
     *
     * @return Command\Edit New Edit command object.
     */
    public function newEditCommand($layout, $recordId, $updatedValues = array()) {
        return new Command\Edit($this, $layout, $recordId, $updatedValues);
    }

    /**
     * Creates a new FileMaker_Command_Delete object.
     *
     * @param string $layout Layout to delete record from.
     * @param string $recordId ID of the record to delete.
     *
     * @return Command\Delete New Delete command object.
     */
    public function newDeleteCommand($layout, $recordId) {
        return new Command\Delete($this, $layout, $recordId);
    }

    /**
     * Creates a new FileMaker_Command_Duplicate object.
     *
     * @param string $layout Layout that the record to duplicate is in.
     * @param string $recordId ID of the record to duplicate.
     *
     * @return Command\Duplicate New Duplicate command object.
     */
    public function newDuplicateCommand($layout, $recordId) {
        return new Command\Duplicate($this, $layout, $recordId);
    }

    /**
     * Creates a new FileMaker_Command_Find object.
     *
     * @param string $layout Layout to find records in.
     *
     * @return Command\Find New Find command object.
     */
    public function newFindCommand($layout) {
        return new Command\Find($this,$layout);
    }

    /**
     * 
     * Creates a new FileMaker_Command_CompoundFind object.
     *
     * @param string $layout Layout to find records in.
     *
     * @return Command\CompoundFind New Compound Find Set command 
     *         object.
     */
    public function newCompoundFindCommand($layout) {
        return new Command\CompoundFind($this, $layout);
    }

    /**
     * 
     * Creates a new FileMaker_Command_FindRequest object. Add one or more 
     * Find Request objects to a {@link FileMaker_Command_CompoundFind} object, 
     * then execute the Compound Find command.
     *
     * @param string $layout Layout to find records in.
     *
     * @return Command\FindRequest New Find Request command object.
     */
    public function newFindRequest($layout) {
        return new Command\FindRequest($this, $layout);
    }

    /**
     * Creates a new FileMaker_Command_FindAny object.
     *
     * @param string $layout Layout to find one random record from.
     *
     * @return Command\FindAny New Find Any command object.
     */
    public function newFindAnyCommand($layout) {
        return new Command\FindAny($this, $layout);
    }

    /**
     * Creates a new FileMaker_Command_FindAll object.
     *
     * @param string $layout Layout to find all records in.
     *
     * @return Command\FindAll New Find All command object.
     */
    public function newFindAllCommand($layout) {
        return new Command\FindAll($this, $layout);
    }

    /**
     * Creates a new FileMaker_Command_PerformScript object.
     *
     * @param string $layout Layout to use for script context.
     * @param string $scriptName Name of the ScriptMaker script to run.
     * @param string $scriptParameters Any parameters to pass to the script.
     *
     * @return Command\PerformScript New Perform Script command 
     *         object.
     */
    public function newPerformScriptCommand($layout, $scriptName, $scriptParameters = null) {
        return new Command\PerformScript($this, $layout, $scriptName, $scriptParameters);
    }

    /**
     * Creates a new FileMaker_Record object. 
     * 
     * This method does not save the new record to the database. 
     * The record is not created on the Database Server until you call 
     * this record's commit() method. You must specify a layout name, 
     * and you can optionally specify an array of field values. 
     * Individual field values can also be set in the new record object.
     * 
     *
     * @param string $layout Layout to create a new record for.
     * @param array $fieldValues Initial values for the new record's fields.
     *
     * @return FileMaker_Record New Record object.
     */
    public function createRecord($layout, $fieldValues = array()) {
        $layout = $this->getLayout($layoutName);
        if (FileMaker::isError($layout)) {
            return $layout;
        }
        $record = new $this->_properties['recordClass']($layout);
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

    /**
     * Returns a single FileMaker_Record object matching the given
     * layout and record ID, or a FileMaker_Error object, if this operation
     * fails.
     *
     * @param string $layout Layout that $recordId is in.
     * @param string $recordId ID of the record to get.
     *
     * @return FileMaker_Record|FileMaker_Error Record or Error object.
     */
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
     * Returns a Layout object that describes the specified layout.
     *
     * @param string $layout Name of the layout to describe.
     *
     * @return FileMaker_Layout|FileMaker_Error Layout or Error object.
     */
    public function getLayout($layout) {
        static $_layouts = array();
        if (isset(self::$_layouts[$layoutName])) {
            return self::$_layouts[$layoutName];
        }
        $request = $this->execute(array('-db' => $this->getProperty('database'),
            '-lay' => $layoutName,
            '-view' => true));
        if (FileMaker::isError($request)) {
            return $request;
        }
        $parser = new airmoi\FileMaker\Parser\FMResultSet($this);
        $result = $parser->parse($request);
        if (FileMaker::isError($result)) {
            return $result;
        }
        $layout = new airmoi\FileMaker\Layout($this);
        $result = $parser->setLayout($layout);
        if (FileMaker::isError($result)) {
            return $result;
        }
        self::$_layouts[$layoutName] = $layout;
        return $layout;
    }

    /**
     * Returns an array of databases that are available with the current
     * server settings and the current user name and password
     * credentials.
     *
     * @return array|FileMaker_Error List of database names or an Error object.
     */
    public function listDatabases() {
        $request = $this->execute(array('-dbnames' => true));
        if (FileMaker::isError($request)) {
            return $request;
        }
        $parser = new airmoi\FileMaker\Parser\FMResultSet($this);
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

    /**
     * Returns an array of ScriptMaker scripts from the current database that 
     * are available with the current server settings and the current user 
     * name and password credentials.
     *
     * @return array|FileMaker_Error List of script names or an Error object.
     */
    public function listScripts() {
        $request = $this->execute(array('-db' => $this->getProperty('database'),
            '-scriptnames' => true));
        if (FileMaker::isError($request)) {
            return $request;
        }
        $parser = new airmoi\FileMaker\Parser\FMResultSet($this);
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

    /**
     * Returns an array of layouts from the current database that are
     * available with the current server settings and the current
     * user name and password credentials.
     *
     * @return array|FileMaker_Error List of layout names or an Error object.
     */
    public function listLayouts() {
        $request = $this->execute(array('-db' => $this->getProperty('database'),
            '-layoutnames' => true));
        if (FileMaker::isError($request)) {
            return $request;
        }
        $parser = new FMResultSet($this);
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

    /**
     * Returns the data for the specified container field.
     * Pass in a URL string that represents the file path for the container 
     * field contents. For example, get the image data from a container field 
     * named 'Cover Image'. For a FileMaker_Record object named $record, 
     * URL-encode the path returned by the getField() method.  For example:
     * 
     * <samp>
     * <IMG src="img.php?-url=<?php echo urlencode($record->getField('Cover Image')); ?>">
     * </samp>
     * 
     * Then as shown below in a line from img.php, pass the URL into 
     * getContainerData() for the FileMaker object named $fm:
     * 
     * <samp>
     * echo $fm->getContainerData($_GET['-url']);
     * </samp>
     *
     * @param string $url URL of the container field contents to get.
     *
     * @return string Raw field data|FileMaker_Error if remote container field.
     */
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
        $this->log('Request for ' . $hostspec, self::LOG_INFO);
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

    /**
     * Perform xml query to FM Server
     * @param array $params
     * @param string $grammar fm xml grammar
     * @return \airmoi\FileMaker\FileMaker_Error
     */
    public function execute($params, $grammar = 'fmresultset') {
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
       // $this->log('Request for ' . $host, FileMaker::LOG_INFO);
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
        //$this->log($curlResponse, FileMaker::LOG_DEBUG);
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

    /**
     * Returns the fully qualified URL for the specified container field.
     * Pass in a URL string that represents the file path for the container 
     * field contents. For example, get the URL for a container field 
     * named 'Cover Image'.  For example:
     * 
     * <samp>
     * <IMG src="<?php echo $fm->getContainerDataURL($record->getField('Cover Image')); ?>">
     * </samp>
     *
     * @param string $url URL of the container field contents to get.
     *
     * @return string Fully qualified URL to container field contents
     */
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

    /**
     * Set curl Sesion cookie
     * @param Resource $curl a cUrl handle ressource
     */
    private function _setCurlWPCSessionCookie($curl) {
        if (isset($_COOKIE["WPCSessionID"])) {
            $WPCSessionID = $_COOKIE["WPCSessionID"];
            if (!is_null($WPCSessionID)) {
                $header = "WPCSessionID=" . $WPCSessionID;
                curl_setopt($curl, CURLOPT_COOKIE, $header);
            }
        }
    }

    /**
     * Pass WPC sesion cookie to client for later auth
     * @param string $curlResponse a curl response
     */
    private function _setClientWPCSessionCookie($curlResponse) {
        $found = preg_match('/WPCSessionID=(\d+?);/m', $curlResponse, $matches);
        if ($found) {
            setcookie("WPCSessionID", $matches[1]);
        }
    }

    /**
     * 
     * @param string $curlResponse a curl response
     * @return int content length, -1 if not provided by headers
     */
    private function _getContentLength($curlResponse) {
        $found = preg_match('/Content-Length: (\d+)/', $curlResponse, $matches);
        if ($found) {
            return $matches[1];
        } else {
            return -1;
        }
    }

    /**
     * 
     * @param string $curlResponse  a curl response
     * @return string curlResponse without xml header
     */
    private function _eliminateXMLHeader($curlResponse) {
        $isXml = strpos($curlResponse, "<?xml");
        if ($isXml !== false) {
            return substr($curlResponse, $isXml);
        } else {
            return $curlResponse;
        }
    }

    /**
     * 
     * @param string $curlResponse  a curl response
     * @return string cUrl Response without leading carriage return
     */
    private function _eliminateContainerHeader($curlResponse) {
        $len = strlen("\r\n\r\n");
        $pos = strpos($curlResponse, "\r\n\r\n");
        if ($pos !== false) {
            return substr($curlResponse, $pos + $len);
        } else {
            return $curlResponse;
        }
    }

    /**
     * magic setter
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value) {
        if (array_key_exists($name, $this->_properties)) {
            $this->_properties[$name] = $value;
        } else {
            /**
             * @todo throw Exception
             */
            return false;
        }
    }

}
