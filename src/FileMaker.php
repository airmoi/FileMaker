<?php
/**
 * FileMaker API for PHP
 *
 * @package FileMaker
 * @version 2.0.5
 * 
 * @copyright Copyright (c) 2016 by 1-more-thing (http://1-more-thing.com) All rights reserved.
 * @licence BSD
 */

namespace airmoi\FileMaker;

use airmoi\FileMaker\Parser\FMResultSet;
use airmoi\FileMaker\Object\Layout;

/**
 * Base FileMaker class. Defines database properties, connects to a database,
 * and gets information about the API.
 *
 * @package FileMaker
 * 
 * @author Romain Dunand <airmoi@gmail.com>
 */
class FileMaker {

    /**
     *
     * @var array The FileMaker connection properties
     * You may access and manipulate thoes properties using 
     * [[getProperty()]] and [[setPropety()]] methods
     */
    private $_properties = [
        'charset' => 'utf-8',
        'locale' => 'en',
        'logLevel' => 3,
        'hostspec' => 'http://127.0.0.1',
        'database' => '',
        'username' => '',
        'password' => '',
        'recordClass' => Object\Record::class,
        'prevalidate' => false,
        'curlOptions' => [CURLOPT_SSL_VERIFYPEER => false],
        'dateFormat' => null,
        'useCookieSession' => false,
        'emptyAsNull' => false, //Returns null value instead of empty strings on empty field value
        'errorHandling' => 'default', //Default to use old school FileMaker Errors trapping, 'exception' to handle errors as exceptions
    ];
    
    /**
     * @var \Log PEAR Log object
     */
    private $_logger = null;
    
    /**
     * @var Layout[] a pseudo cache for layouts to prevent unnecessary call's to Custom Web Publishing engine 
     */
    private static $_layouts = [];

    /**
     * @var string Store the last URL call to Custom Web Publishing engine
     */
    public $lastRequestedUrl;

    /**
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
     * Use with the {@link Command\Find::setLogicalOperator()}
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
     * Use with the {@link Command\Find::addSortRule()} and
     * {@link Command\CompoundFind::addSortRule()} methods.
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
     * @return boolean TRUE, if the variable is a {@link FileMakerException} object.
     * @const
     *
     */
    public static function isError($variable) {
        return $variable instanceof FileMakerException;
    }

    /**
     * Returns the version of the FileMaker API for PHP.
     *
     * @return string API version.
     * @const
     */
    public function getAPIVersion() {
        return '2.0.5';
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
     * parameters, pass in NULL for the parameters you want to omit.
     * For example, to specify only the database name, username, and
     * password, but omit the hostspec, call the constructor as follows:
     *
     * @example new FileMaker('DatabaseName', NULL, 'username', 'password');
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
        if(!array_key_exists($prop, $this->_properties)) {
            $error = new FileMakerException($this, 'Unsupported property ' . $prop);
            if($this->getProperty('errorHandling') === 'default'){
                return $error;
            }
            throw $error;
        }
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
     * @param \Log|FileMakerException $logger PEAR Log object.
     * @throws FileMakerException
     */
    public function setLogger($logger) {
        /**
         * @todo handle generic logger ?
         */
        if (!is_a($logger, 'Log')) {
            $error = new FileMakerException($this, 'setLogger() must be passed an instance of PEAR::Log');
            if($this->fm->getProperty('errorHandling') == 'default') {
                return $error;
            }
            throw $error;
        }
        $this->_logger = $logger;
    }

    /**
     * Creates a new Command\Add object.
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
     * Creates a Command\Edit object.
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
     * Creates a new Command\Delete object.
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
     * Creates a new Command\Duplicate object.
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
     * Creates a new Command\Find object.
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
     * Creates a new Command\CompoundFind object.
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
     * Creates a new Command\FindRequest object. Add one or more
     * Find Request objects to a {@link Command\CompoundFind} object,
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
     * Creates a new Command\FindAny object.
     *
     * @param string $layout Layout to find one random record from.
     *
     * @return Command\FindAny New Find Any command object.
     */
    public function newFindAnyCommand($layout) {
        return new Command\FindAny($this, $layout);
    }

    /**
     * Creates a new Command\FindAll object.
     *
     * @param string $layout Layout name to find all records in.
     *
     * @return Command\FindAll New Find All command object.
     */
    public function newFindAllCommand($layout) {
        return new Command\FindAll($this, $layout);
    }

    /**
     * Creates a new Command\PerformScript object.
     *
     * @param string $layout Layout name to use for script context.
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
     * Creates a new Object\Record object.
     *
     * This method does not save the new record to the database.
     * The record is not created on the Database Server until you call
     * this record's commit() method. You must specify a layout name,
     * and you can optionally specify an array of field values.
     * Individual field values can also be set in the new record object.
     *
     *
     * @param string $layoutName Layout name to create a new record for.
     * @param array $fieldValues Initial values for the new record's fields.
     *
     * @return Object\Record|FileMakerException New Record object or a FileMakerError.
     * @throws FileMakerException
     */
    public function createRecord($layoutName, $fieldValues = array()) {
        $layout = $this->getLayout($layoutName);
        $record = new $this->_properties['recordClass']($layout);
        /* @var $record Object\Record */
        if (is_array($fieldValues)) {
            foreach ($fieldValues as $fieldName => $fieldValue) {
                if (is_array($fieldValue)) {
                    foreach ($fieldValue as $repetition => $value) {
                        $error = $record->setField($fieldName, $value, $repetition);
                    }
                } else {
                    $error = $record->setField($fieldName, $fieldValue);
                }
                if(FileMaker::isError($error)){
                    return $error;
                }
            }
        }
        return $record;
    }

    /**
     * Returns a single Object\Record object matching the given
     * layout and record ID, or throws a FileMakerException object, if this operation
     * fails.
     *
     * @param string $layout Layout that $recordId is in.
     * @param string $recordId ID of the record to get.
     *
     * @return Object\Record|FileMakerException
     * @throws FileMakerException
     */
    public function getRecordById($layout, $recordId) {
        $request = $this->newFindCommand($layout);
        $request->setRecordId($recordId);
        $result = $request->execute();
        if (FileMaker::isError($result)) {
            return $request;
        }
        
        $record = $result->getRecords();
        if (!$record) {
            $error = new FileMakerException($this, 'Record . ' . $recordId . ' not found in layout "' . $layout . '".');
            if($this->fm->getProperty('errorHandling') == 'default') {
                return $error;
            }
            throw $error;
        }
        return $record[0];
    }

    /**
     * Returns a Layout object that describes the specified layout.
     *
     * @param string $layoutName Name of the layout to describe.
     *
     * @return Layout|FileMakerException Layout.
     * @throws FileMakerException
     */
    public function getLayout($layoutName) {
        if (isset(self::$_layouts[$layoutName]) ) {
            return self::$_layouts[$layoutName];
        }
        
        $request = $this->execute(array('-db' => $this->getProperty('database'),
            '-lay' => $layoutName,
            '-view' => true));
        if (FileMaker::isError($request)) {
            return $request;
        }
        
        $parser = new FMResultSet($this);
        $result = $parser->parse($request);
        $layout = new Layout($this);
        $result = $parser->setLayout($layout);
        self::$_layouts[$layoutName] = $layout;
        return $layout;
    }

    /**
     * Returns an array of databases that are available with the current
     * server settings and the current user name and password
     * credentials.
     *
     * @return array|FileMakerException List of database names.
     * @throws FileMakerException
     */
    public function listDatabases() {
        $request = $this->execute(array('-dbnames' => true));
        if (FileMaker::isError($request)) {
            return $request;
        }
        
        $parser = new FMResultSet($this);
        $result = $parser->parse($request);

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
     * @return array|FileMakerException List of script names.
     * @throws FileMakerException
     */
    public function listScripts() {
        $request = $this->execute(array('-db' => $this->getProperty('database'),
            '-scriptnames' => true));
        if (FileMaker::isError($request)) {
            return $request;
        }
        
        $parser = new FMResultSet($this);
        $result = $parser->parse($request);

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
     * @return array|FileMakerException List of layout names.
     * @throws FileMakerException
     */
    public function listLayouts() {
        $request = $this->execute(array('-db' => $this->getProperty('database'),
            '-layoutnames' => true));
        if (FileMaker::isError($request)) {
            return $request;
        }
        
        $parser = new FMResultSet($this);
        $result = $parser->parse($request);

        $list = array();
        foreach ($parser->parsedResult as $data) {
            $list[] = $data['fields']['LAYOUT_NAME'][0];
        }
        return $list;
    }
    
    function log($message, $level)
    {
        if ($this->_logger === null) {
            return;
        }
        
        $logLevel = $this->getProperty('logLevel');
        if ($logLevel === null || $level > $logLevel) {
            return;
        }
        switch ($level) {
            case self::LOG_DEBUG:
                $this->_logger->log($message, PEAR_LOG_DEBUG);
                break;
            case self::LOG_INFO:
                $this->_logger->log($message, PEAR_LOG_INFO);
                break;
            case self::LOG_ERR:
                $this->_logger->log($message, PEAR_LOG_ERR);
                break;
       }
}

    /**
     * Returns the data for the specified container field.
     * Pass in a URL string that represents the file path for the container
     * field contents. For example, get the image data from a container field
     * named 'Cover Image'. For a Object\Record object named $record,
     * URL-encode the path returned by the getField() method.  For example:
     *
     * @example <IMG src="img.php?-url=<?php echo urlencode($record->getField('Cover Image')); ?>">
     *
     * Then as shown below in a line from img.php, pass the URL into
     * getContainerData() for the FileMaker object named $fm:
     *
     * @example echo $fm->getContainerData($_GET['-url']);
     *
     * @param string $url URL of the container field contents to get.
     *
     * @return string|FileMakerException Raw field data.
     * @throws FileMakerException if remote container field or curl not active.
     */
    public function getContainerData($url) {
        if (!function_exists('curl_init')) {
            $error = new FileMakerException($this, 'cURL is required to use the FileMaker API.');
            if($this->getProperty('errorHandling') == 'default') {
                return $error;
            }
            throw $error;
        }
        if (strncasecmp($url, '/fmi/xml/cnt', 11) != 0) {
            $error = new FileMakerException($this, 'getContainerData() does not support remote containers');
            if($this->getProperty('errorHandling') == 'default') {
                return $error;
            }
            throw $error;
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
            $error = new FileMakerException($this, 'cURL Communication Error: (' . $curlError . ') ' . curl_error($curl));
            if($this->fm->getProperty('errorHandling') == 'default') {
                return $error;
            }
            throw $error;
        }
        curl_close($curl);
        return $curlResponse;
    }

    /**
     * Perform xml query to FM Server
     *
     * @param array $params
     * @param string $grammar fm xml grammar
     *
     * @return string|FileMakerException the cUrl response
     * @throws FileMakerException
     */
    public function execute($params, $grammar = 'fmresultset') {
        if (!function_exists('curl_init')) {
            $error = new FileMakerException($this, 'cURL is required to use the FileMaker API.');
            if($this->fm->getProperty('errorHandling') == 'default') {
                return $error;
            }
            throw $error;
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
        $this->lastRequestedUrl = $host . '?' . implode('&', $RESTparams);
        $this->log($this->lastRequestedUrl, FileMaker::LOG_DEBUG);
        
        $curlResponse = curl_exec($curl);
        $this->log($curlResponse, FileMaker::LOG_DEBUG);
        if ($curlError = curl_errno($curl)) {

            if ($curlError == 52) {
                $error = new FileMakerException( $this, 'cURL Communication Error: (' . $curlError . ') ' . curl_error($curl) . ' - The Web Publishing Core and/or FileMaker Server services are not running.', $curlError);

            } else if ($curlError == 22) {
                if (stristr("50", curl_error($curl))) {
                    $error = new FileMakerException( $this, 'cURL Communication Error: (' . $curlError . ') ' . curl_error($curl) . ' - The Web Publishing Core and/or FileMaker Server services are not running.', $curlError);
                } else {
                    $error = new FileMakerException( $this, 'cURL Communication Error: (' . $curlError . ') ' . curl_error($curl) . ' - This can be due to an invalid username or password, or if the FMPHP privilege is not enabled for that user.', $curlError);
                }
            } else {
                $error = new FileMakerException( $this, 'cURL Communication Error: (' . $curlError . ') ' . curl_error($curl), $curlError);
            }
            if($this->fm->getProperty('errorHandling') == 'default') {
                return $error;
            }
            throw $error;
        }
        curl_close($curl);

        $this->_setClientWPCSessionCookie($curlResponse);
        if ($curlHeadersSent) {
            $curlResponse = $this->_eliminateXMLHeader($curlResponse);
        }

        return $curlResponse;
    }

    /**
     * Returns the fully qualified URL for the specified container field.
     * Pass in a URL string that represents the file path for the container
     * field contents. For example, get the URL for a container field
     * named 'Cover Image'.  For example:
     *
     * @example <IMG src="<?php echo $fm->getContainerDataURL($record->getField('Cover Image')); ?>">
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
        if(!$this->getProperty('useCookieSession')) {
            return;
        }
        if (isset($_COOKIE["WPCSessionID"])) {
            $WPCSessionID = $_COOKIE["WPCSessionID"];
            if (!is_null($WPCSessionID)) {
                $header = "WPCSessionID=" . $WPCSessionID;
                curl_setopt($curl, CURLOPT_COOKIE, $header);
            }
        }
    }

    /**
     * Pass WPC session cookie to client for later auth
     * @param string $curlResponse a curl response
     */
    private function _setClientWPCSessionCookie($curlResponse) {
        if(!$this->getProperty('useCookieSession')) {
            return;
        }
        $found = preg_match('/WPCSessionID="([^;]*)";/m', $curlResponse, $matches);
        /* Update WPCSession Cookie if needed */
        if ($found && @$_COOKIE['WPCSessionID'] != $matches[1]) {
            setcookie("WPCSessionID", $matches[1]);
            $_COOKIE['WPCSessionID'] = $matches[1];
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
     *
     * @param string $name
     * @param mixed $value
     *
     * @return boolean
     */
    public function __set($name, $value) {
        if (array_key_exists($name, $this->_properties)) {
            $this->_properties[$name] = $value;
        } else {
            throw new FileMakerException($this, 'Attempt to set an unsupported property (' . $name . ')');
        }
    }

    public function toOutputCharset($value) {
        if (strtolower($this->getProperty('charset')) != 'iso-8859-1') {
            return $value;
        }
        if (is_array($value)) {
            $output = array();
            foreach ($value as $key => $val) {
                $output[$this->toOutputCharset($key)] = $this->toOutputCharset($val);
            }
            return $output;
        }
        if (!is_string($value)) {
            return $value;
        }
        return utf8_decode($value);
    }

    /**
     * Returns the last URL call to xml engine
     * @return string
     */
    public function getLastRequestedUrl(){
        return $this->lastRequestedUrl;
    }


    public function dateConvertInput($value) {
        if($this->getProperty('dateFormat') === null){
            return $value;
        }
        try {
            $date = \DateTime::createFromFormat($this->getProperty('dateFormat'), $value);
            return $date->format('m/d/Y');
        }  catch (Exception $e) {
            $this->log('Could not convert string to a valid DateTime : ' . $e->getMessage(), FileMaker::LOG_ERR);
            return $value;
        }
    }

    public function dateConvertOutput($value) {
        if($this->getProperty('dateFormat') === null){
            return $value;
        }
        try {
            $date = \DateTime::createFromFormat('m/d/Y', $value);
            return $date->format($this->getProperty('dateFormat'));
        } catch (Exception $e) {
            $this->log('Could not convert string to a valid DateTime : ' . $e->getMessage(), FileMaker::LOG_ERR);
            return $value;
        }
    }

}
