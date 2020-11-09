<?php
/**
 * FileMaker API for PHP
 *
 * @package FileMaker
 *
 * @copyright Copyright (c) 2016 by 1-more-thing (http://1-more-thing.com) All rights reserved.
 * @license BSD
 */

namespace airmoi\FileMaker;

use airmoi\FileMaker\Helpers\DataApi;
use airmoi\FileMaker\Parser\DataApiResult;
use airmoi\FileMaker\Parser\FMResultSet;
use airmoi\FileMaker\Object\Layout;
use DateTime;
use Exception;
use ReflectionException;
use ReflectionMethod;

/**
 * Base FileMaker class. Defines database properties, connects to a database,
 * and gets information about the API.
 *
 * @package FileMaker
 *
 * @author Romain Dunand <airmoi@gmail.com>
 *
 * @property string $charset                Default to 'utf-8'
 * @property Object|null $cache                  Default null
 * @property Object|null sessionHandler          Default null
 * @property bool $schemaCache            Default to true, enable cache to prevent unnecessary queries
 * @property int $schemaCacheDuration    Default to 3600
 * @property string $locale                 Default to 'en' (possible values : en, de, fr, it, ja, sv)
 * @property int $logLevel               Default to 3 (PEAR_LOG_ERR)
 * @property string $hostspec               Default to '127.0.0.1'
 * @property string $database
 * @property string $username
 * @property string $password
 * @property string $recordClass            Default to 'Object/Record'
 * @property bool $prevalidate            Default to false
 * @property array $curlOptions            Default to [CURLOPT_SSL_VERIFYPEER => false]
 * @property string $dateFormat
 * @property bool $useDateFormatInRequests    Whether to convert date input in query strings
 * @property bool $useCookieSession       Default to false
 * @property bool $emptyAsNull            Return null instead of empty strings, default to false
 * @property bool $useDataApi             default false
 */
class FileMaker
{
    private static $apiVersion = '3.0.0-alpha';
    private static $minServerVersion = '18.0.0.0';

    private $token = null;
    /**
     *
     * @var array The FileMaker connection properties
     * You may access and manipulate thoes properties using
     * [[getProperty()]] and [[setPropety()]] methods
     */
    private $properties = [
        'charset' => 'utf-8',
        'useCache' => true,
        'schemaCache' => true,
        'schemaCacheDuration' => 3600,
        'cache' => null,
        'sessionHandler' => null,
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
        'useDateFormatInRequests' => false,
        'useCookieSession' => false,
        'emptyAsNull' => false, //Returns null value instead of empty strings on empty field value
        'errorHandling' => 'exception', //Default to use old school FileMaker Errors trapping
        'enableProfiling' => false,
        'useDataApi' => false
    ];

    /**
     * @var Object Log object
     */
    private $logger = null;

    /**
     * @var array[] a pseudo cache for scripts list to prevent unnecessary call's to Custom Web Publishing engine
     */
    private static $scripts = [];

    private static $internalCache = [];

    /**
     * @var string Store the last URL call to Custom Web Publishing engine
     */
    private $lastRequestedUrl;

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
    const LOG_NOTICE = 5;
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
    public static function isError($variable)
    {
        return $variable instanceof FileMakerException;
    }

    /**
     * Returns the version of the FileMaker API for PHP.
     *
     * @return string API version.
     * @const
     */
    public static function getAPIVersion()
    {
        return self::$apiVersion;
    }

    /**
     * Returns the minimum version of FileMaker Server that this API works with.
     *
     * @return string Minimum FileMaker Server version.
     * @const
     */
    public static function getMinServerVersion()
    {
        return self::$minServerVersion;
    }

    /**
     * FileMaker object constructor.
     *
     * If you want to use the constructor without specifying all the
     * parameters, pass in NULL for the parameters you want to omit.
     * For example, to specify only the database name, username, and
     * password, but omit the hostspec, call the constructor as follows:
     *
     * @param string $database Name of the database to connect to.
     * @param string $hostspec Hostspec of web server in FileMaker Server
     *        deployment. Defaults to http://localhost, if set to NULL.
     * @param string $username Account name to log into database.
     * @param string $password Password for account.
     * @param array $options An array of options.
     * @throws FileMakerException
     * @example new FileMaker('DatabaseName', NULL, 'username', 'password');
     *
     */
    public function __construct($database = null, $hostspec = null, $username = null, $password = null, $options = [])
    {
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

        foreach ($options as $key => $value) {
            $this->setProperty($key, $value);
        }
    }

    /**
     * @throws FileMakerException
     */
    public function __destruct()
    {
        //Logout dataAPI session if the token cannot be saved to be reused in next query (cli mode or no active session)
        //If a session handler is set, we assume the token has been saved (session may have been closed before destruct was called)
        if ($this->token && $this->useDataApi && !$this->sessionHandler && !session_id() || php_sapi_name() === 'cli') {
            $this->dataApiLogout();
        }
    }

    /**
     * Sets a property to a new value for all API calls.
     *
     * @param string $prop Name of the property to set.
     * @param string $value Property's new value.
     * @return FileMakerException|null
     * @throws FileMakerException
     */
    public function setProperty($prop, $value)
    {
        if (!array_key_exists($prop, $this->properties)) {
            return $this->returnOrThrowException('Unsupported property ' . $prop);
        }
        $this->properties[$prop] = $value;
        return null;
    }

    /**
     * Returns the current value of a property.
     *
     * @param string $prop Name of the property.
     *
     * @return string|array Property's current value.
     */
    public function getProperty($prop)
    {
        return isset($this->properties[$prop]) ? $this->properties[$prop] : null;
    }

    /**
     * Returns an associative array of property name => property value for
     * all current properties and their current values.
     *
     * This array enables PHP object introspection and debugging when necessary.
     *
     * @return array All current properties.
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Associates a Log object with the API for logging requests
     * and responses.
     * Logger must implement a log(strinq $message, int $level) method
     *
     * @param Object|FileMakerException $logger Log object.
     * @return FileMakerException|void
     * @throws FileMakerException
     */
    public function setLogger($logger)
    {
        if (!method_exists($logger, 'log')) {
            return $this->returnOrThrowException('setLogger() must be passed an class that implements log(strinq $message, int $level) method');
        }
        $this->logger = $logger;
    }

    /**
     * Associates a Cache object with the API for caching
     * Cache object must implement a set(strinq $key, mixed $value, int $duration) method
     * and a get(strinq $key) method
     *
     * @param Object|FileMakerException $cache Cache object.
     * @return FileMakerException|void
     * @throws FileMakerException
     */
    public function setCache($cache)
    {
        if (!method_exists($cache, 'set') || !method_exists($cache, 'get')) {
            return $this->returnOrThrowException('setCache() must be passed an class that implements set(strinq $key, mixed $value, int $duration) and get(strinq $key) methods');
        }
        $this->cache = $cache;
    }

    /**
     * Associates a SessionHandler object with the API for session management
     * Session handler object must implement a set(strinq $key, mixed $value) method
     * and a get(strinq $key) method
     *
     * @param $handler
     * @return FileMakerException|void
     * @throws FileMakerException
     */
    public function setSessionHandler($handler)
    {
        if (!method_exists($handler, 'set') || !method_exists($handler, 'get')) {
            return $this->returnOrThrowException('setCache() must be passed an class that implements set(strinq $key, mixed $value) and get(string $key) methods');
        }
        $this->sessionHandler = $handler;
    }

    public function getToken()
    {
        return $this->token;
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
     * @param bool $useRawData
     * @return Command\Add New Add command object.
     * @throws FileMakerException
     */
    public function newAddCommand($layout, $values = [], $useRawData = false)
    {
        return new Command\Add($this, $layout, $values, $useRawData);
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
     * @param bool $useRawData Prevent date/time conversion when values are already
     *
     * @param null $relatedSetName
     * @return Command\Edit New Edit command object.
     * @throws FileMakerException
     */
    public function newEditCommand($layout, $recordId, $updatedValues = [], $useRawData = false, $relatedSetName = null)
    {
        return new Command\Edit($this, $layout, $recordId, $updatedValues, $useRawData, $relatedSetName);
    }

    /**
     * Creates a new Command\Delete object.
     *
     * @param string $layout Layout to delete record from.
     * @param string $recordId ID of the record to delete.
     *
     * @return Command\Delete New Delete command object.
     */
    public function newDeleteCommand($layout, $recordId)
    {
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
    public function newDuplicateCommand($layout, $recordId)
    {
        return new Command\Duplicate($this, $layout, $recordId);
    }

    /**
     * Creates a new Command\Find object.
     *
     * @param string $layout Layout to find records in.
     *
     * @return Command\Find New Find command object.
     */
    public function newFindCommand($layout)
    {
        return new Command\Find($this, $layout);
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
    public function newCompoundFindCommand($layout)
    {
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
     *
     */
    public function newFindRequest($layout)
    {
        return new Command\FindRequest($this, $layout);
    }

    /**
     * Creates a new Command\FindAny object.
     *
     * @param string $layout Layout to find one random record from.
     *
     * @return Command\FindAny New Find Any command object.
     */
    public function newFindAnyCommand($layout)
    {
        return new Command\FindAny($this, $layout);
    }

    /**
     * Creates a new Command\FindAll object.
     *
     * @param string $layout Layout name to find all records in.
     *
     * @return Command\FindAll New Find All command object.
     */
    public function newFindAllCommand($layout)
    {
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
    public function newPerformScriptCommand($layout, $scriptName, $scriptParameters = null)
    {
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
    public function createRecord($layoutName, $fieldValues = [])
    {
        $layout = $this->getLayout($layoutName);
        $record = new $this->properties['recordClass']($layout);
        $error = false;

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
                if (FileMaker::isError($error)) {
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
    public function getRecordById($layout, $recordId)
    {
        $request = $this->newFindCommand($layout);
        $request->setRecordId($recordId);
        $result = $request->execute();
        if (FileMaker::isError($result)) {
            return $result;
        }

        $record = $result->getFirstRecord();
        if (!$record) {
            return $this->returnOrThrowException('Record . ' . $recordId . ' not found in layout "' . $layout . '".');
        }
        return $record;
    }

    /**
     * Returns a Layout object that describes the specified layout.
     *
     * @param string $layoutName Name of the layout to describe.
     *
     * @param string|null $recid
     * @param bool $loadExtended
     * @return Layout|FileMakerException Layout.
     * @throws FileMakerException
     */
    public function getLayout($layoutName, $recid = null, $loadExtended = true)
    {
        if ($recid === null && $layout = $this->cacheGet('layout-' . $layoutName)) {
            return $layout;
        }

        $request = $this->execute([
            '-db' => $this->getProperty('database'),
            '-lay' => $layoutName,
            '-view' => true
        ]);

        if (FileMaker::isError($request)) {
            return $request;
        }

        if (!$this->useDataApi) {
            $parser = new FMResultSet($this);
        } else {
            $parser = new DataApiResult($this);
        }
        $result = $parser->parse($request);
        if (FileMaker::isError($result)) {
            return $result;
        }

        $layout = new Layout($this);
        $result = $parser->setLayout($layout);
        if (FileMaker::isError($result)) {
            return $result;
        }
        $layout->name = $layoutName;

        //Load a random record to get extra meta data
        if (!$layout->table && $loadExtended) {
            $this->newFindAllCommand($layout->name)->setRange(0, 1)->execute();
            $layoutExtended = $this->cacheGet('layout-' . $layoutName);
            $layout->name = $layoutExtended->name;
            $layout->database = $layoutExtended->database;
            $layout->table = $layoutExtended->table;
        }

        //Cache only if no recid provided
        if (!$recid) {
            $this->cacheSet('layout-' . $layoutName, $layout);
        }
        return $layout;
    }

    /**
     * Returns an array of databases that are available with the current
     * server settings and the current user name and password
     * credentials.
     *
     * @return string[]|FileMakerException List of database names.
     * @throws FileMakerException
     */
    public function listDatabases()
    {
        $request = $this->execute(['-dbnames' => true]);
        if (FileMaker::isError($request)) {
            return $request;
        }


        if (!$this->useDataApi) {
            $parser = new FMResultSet($this);
        } else {
            $parser = new DataApiResult($this);
        }

        $result = $parser->parse($request);
        if (FileMaker::isError($result)) {
            return $request;
        }

        $list = [];

        if (!$this->useDataApi) {
            foreach ($parser->parsedResult as $data) {
                $list[] = $data['fields']['DATABASE_NAME'][0];
            }
        } else {
            foreach ($parser->parsedResult['databases'] as $data) {
                $list[] = $data['name'];
            }
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
    public function listScripts()
    {
        if ($this->schemaCache && isset(self::$scripts[$this->connexionId()])) {
            return self::$scripts[$this->connexionId()];
        }

        $request = $this->execute([
            '-db' => $this->getProperty('database'),
            '-scriptnames' => true
        ]);
        if (FileMaker::isError($request)) {
            return $request;
        }

        if (!$this->useDataApi) {
            $parser = new FMResultSet($this);
        } else {
            $parser = new DataApiResult($this);
        }

        $result = $parser->parse($request);
        if (FileMaker::isError($result)) {
            return $result;
        }

        $list = [];
        if (!$this->useDataApi) {
            foreach ($parser->parsedResult as $data) {
                $list[] = $data['fields']['SCRIPT_NAME'][0];
            }
        } else {
            foreach ($parser->parsedResult['scripts'] as $data) {
                if (!$data['isFolder']) {
                    $list[] = $data['name'];
                }
            }
        }

        if ($this->schemaCache) {
            self::$scripts[$this->connexionId()] = $list;
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
    public function listLayouts()
    {
        $request = $this->execute([
            '-db' => $this->getProperty('database'),
            '-layoutnames' => true
        ]);
        if (FileMaker::isError($request)) {
            return $request;
        }

        if (!$this->useDataApi) {
            $parser = new FMResultSet($this);
        } else {
            $parser = new DataApiResult($this);
        }
        $result = $parser->parse($request);
        if (FileMaker::isError($result)) {
            return $result;
        }

        $list = [];
        if (!$this->useDataApi) {
            foreach ($parser->parsedResult as $data) {
                $list[] = $data['fields']['LAYOUT_NAME'][0];
            }
        } else {
            $list = $this->parseLayoutsRecursive($parser->parsedResult['layouts']);
        }
        return $list;
    }

    private function parseLayoutsRecursive(array $layouts)
    {
        $list = [];
        foreach ($layouts as $layout) {
            if (@$layout['isFolder']) {
                $list = array_merge($list, $this->parseLayoutsRecursive($layout['folderLayoutNames']));
            } else {
                $list[] = $layout['name'];
            }
        }
        return $list;
    }

    /**
     * @param string $message
     * @param int $level
     */
    public function log($message, $level)
    {
        if ($this->logger === null) {
            return;
        }

        $logLevel = $this->getProperty('logLevel');
        if ($logLevel === null || $level > $logLevel) {
            return;
        }
        $this->logger->log($message, $level);
    }

    /**
     * @param string $token
     */
    public function beginProfile($token)
    {
        if ($this->logger === null || !$this->getProperty('enableProfiling')) {
            return;
        }

        if (!method_exists($this->logger, 'profileBegin')) {
            $this->log("Your logger must implement a profileBegin(\$token) method to handle profiling", self::LOG_ERR);
            return;
        }
        $this->logger->profileBegin($token);
    }

    /**
     * @param string $key string key identifying the cached value.
     *
     * @return bool|mixed The value stored in cache, false if the value is not in the cache or expired.
     */
    public function cacheGet($key)
    {
        if ($this->cache === null || !$this->schemaCache) {
            if (isset(self::$internalCache[$this->connexionId() . '-' . $key])) {
                return self::$internalCache[$this->connexionId() . '-' . $key];
            }
        } else {
            return $this->cache->get($this->connexionId() . '-' . $key);
        }
        return null;
    }

    /**
     * @param string $key string A key identifying the value to be cached.
     * @param FileMakerException|Layout $value
     *
     * @return boolean
     */
    public function cacheSet($key, $value)
    {
        if ($this->cache === null || !$this->schemaCache) {
            self::$internalCache[$this->connexionId() . '-' . $key] = $value;
            return true;
        } else {
            return $this->cache->set($this->connexionId() . '-' . $key, $value, $this->schemaCacheDuration);
        }
    }

    /**
     * @param string $key string key identifying the cached value.
     *
     * @return bool|mixed The value stored in cache, false if the value is not in the cache or expired.
     */
    public function sessionGet($key)
    {
        if ($this->sessionHandler === null) {
            if (!session_id() && headers_sent()) {
                return null; //Can't open, session while headers aleady sents
            } elseif (!session_id()) {
                session_start();
            }
            if (isset($_SESSION[$this->connexionId() . '-' . $key])) {
                return $_SESSION[$this->connexionId() . '-' . $key];
            }
        } else {
            return $this->sessionHandler->get($this->connexionId() . '-' . $key);
        }
        return null;
    }

    /**
     * @param string $key string A key identifying the value to be cached.
     * @param $value mixed The value to be cached
     *
     * @return boolean
     */
    public function sessionSet($key, $value)
    {
        if ($this->sessionHandler === null) {
            if (!session_id() && headers_sent()) {
                return null; //Can't open, session while headers aleady sents
            } elseif (!session_id()) {
                session_start();
            }
            $_SESSION[$this->connexionId() . '-' . $key] = $value;
            return true;
        } else {
            return $this->sessionHandler->set($this->connexionId() . '-' . $key, $value);
        }
    }

    /**
     * @param string $token
     */
    public function endProfile($token)
    {
        if ($this->logger === null || !$this->getProperty('enableProfiling')) {
            return;
        }

        if (!method_exists($this->logger, 'profileEnd')) {
            $this->log("Your logger must implement a profileEnd(\$token) method to handle profiling", self::LOG_ERR);
            return;
        }
        $this->logger->profileEnd($token);
    }

    /**
     * Returns the data for the specified container field.
     * Pass in a URL string that represents the file path for the container
     * field contents. For example, get the image data from a container field
     * named 'Cover Image'. For a Object\Record object named $record,
     * URL-encode the path returned by the getField() method.  For example:
     *
     * @param string $url URL of the container field contents to get.
     *
     * @return string|FileMakerException Raw field data.
     * @throws FileMakerException if remote container field or curl not active.
     * @example echo $fm->getContainerData($_GET['-url']);
     *
     * @example <IMG src="img.php?-url=<?php echo urlencode($record->getField('Cover Image')); ?>">
     *
     * Then as shown below in a line from img.php, pass the URL into
     * getContainerData() for the FileMaker object named $fm:
     *
     */
    public function getContainerData($url)
    {
        if (!function_exists('curl_init')) {
            return $this->returnOrThrowException('cURL is required to use the FileMaker API.');
        }

        if (!$this->useDataApi) {
            if (strncasecmp($url, '/fmi/xml/cnt', 11) !== 0) {
                return $this->returnOrThrowException('getContainerData() does not support remote containers');
            } else {
                $hostspec = $this->getProperty('hostspec');
                if (substr($hostspec, -1, 1) === '/') {
                    $hostspec = substr($hostspec, 0, -1);
                }
                $hostspec .= $url;
                $hostspec = htmlspecialchars_decode($hostspec);
                $hostspec = str_replace(" ", "%20", $hostspec);
            }
            //$this->log('Request for ' . $hostspec, self::LOG_INFO);
        } else {
            $hostspec = $url;
        }
        $curl = curl_init($hostspec);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FAILONERROR, true);
        curl_setopt($curl, CURLOPT_HEADER, false);

        if (!$this->useDataApi) {
            $this->setCurlWPCSessionCookie($curl);

            if ($this->getProperty('username')) {
                $authString = base64_encode($this->getProperty('username') . ':' . $this->getProperty('password'));
                $headers = [
                    'Authorization: Basic ' . $authString,
                    'X-FMI-PE-ExtendedPrivilege: IrG6U+Rx0F5bLIQCUb9gOw=='
                ];
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            } else {
                curl_setopt($curl, CURLOPT_HTTPHEADER, ['X-FMI-PE-ExtendedPrivilege: IrG6U+Rx0F5bLIQCUb9gOw==']);
            }
        } else {
            $cookiePath = tempnam(sys_get_temp_dir(), 'fmAPICookie_' . mt_rand());
            curl_setopt($curl, CURLOPT_COOKIEJAR, $cookiePath);
            curl_setopt($curl, CURLOPT_MAXREDIRS, 20);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        }

        if ($curlOptions = $this->getProperty('curlOptions')) {
            foreach ($curlOptions as $key => $value) {
                curl_setopt($curl, $key, $value);
            }
        }

        $this->lastRequestedUrl = $hostspec;
        $this->log("Perform request: " . $this->lastRequestedUrl, FileMaker::LOG_INFO);

        $this->beginProfile($hostspec);
        $curlResponse = curl_exec($curl);
        $this->endProfile($hostspec);
        $curlinfos = curl_getinfo($curl);

        //retry on error 401
        if ($curlinfos['http_code'] === 401 && isset($cookiePath)) {
            curl_setopt($curl, CURLOPT_COOKIEFILE, $cookiePath);
            $curlResponse = curl_exec($curl);
        }

        if ($curlError = curl_errno($curl)) {
            return $this->handleCurlError($curlError, $curl);
        }
        $this->log($curlResponse, FileMaker::LOG_DEBUG);
        if (!$this->useDataApi) {
            $this->setClientWPCSessionCookie($curlResponse);
        }

        curl_close($curl);
        return $curlResponse;
    }

    /**
     * query to FM Server, route to cwp or dataAPI
     *
     * @param array $params
     * @param string $grammar fm xml grammar
     *
     * @return string|FileMakerException the cUrl response
     * @throws FileMakerException
     */
    public function execute($params, $grammar = 'fmresultset')
    {
        if (!function_exists('curl_init')) {
            return $this->returnOrThrowException('cURL is required to use the FileMaker API.');
        }

        if (!$this->getProperty('useDataApi')) {
            return $this->executeCWP($params, $grammar);
        } else {
            return $this->executeDataApi($params);
        }
    }

    /**
     * Perform dataAPI query to FM Server
     *
     * @param $params
     *
     * @return string|FileMakerException the cUrl response
     *
     * @throws FileMakerException
     * @throws Exception
     */
    public function executeDataApi(array $params)
    {
        $globals = DataApi::parseGlobalFields($params);
        $globalQuery = [];
        if ($globals) {
            $layout = $this->getLayout($params['-lay']);
            $globalOptions = array_merge(
                DataApi::appendTableToGlobals($globals, $layout->table),
                ['-setGlobals' => true, '-db' => $params['-db']]
            );
            $globalQuery = DataApi::prepareQuery($globalOptions);
            $globalQuery['headers'][] = 'Authorization: bearer ' . $this->getSessionBearer();

            $response = $this->runDataApiQuery($globalQuery);
            //Token expired
            if (DataApiResult::parseError($response)['code'] == 952) {
                $this->getSessionBearer(true);
                //replay query after token renew
                return $this->executeDataApi($params);
            }

            $parser = new DataApiResult($this);
            $parseResult = $parser->parse($response);
            if (FileMaker::isError($parseResult)) {
                return $parseResult;
            }
        }
        $query = DataApi::prepareQuery($params);
        if (!isset($params['-dbnames'])) {
            $query['headers'][] = 'Authorization: bearer ' . $this->getSessionBearer();
        }

        $response = $this->runDataApiQuery($query);
        //Token expired
        if (DataApiResult::parseError($response)['code'] == 952) {
            $this->getSessionBearer(true);
            //replay query after token renew
            return $this->executeDataApi($params);
        }

        //Reset globals after query
        if ($globals) {
            foreach ($globalQuery['body']['globalFields'] as $field => $value) {
                $globalQuery['body']['globalFields'][$field] = '';
            }
            $responseEmpty = $this->runDataApiQuery($globalQuery);
            $parser = new DataApiResult($this);
            $parseResult = $parser->parse($responseEmpty);
            if (FileMaker::isError($parseResult)) {
                return $parseResult;
            }
        }

        return $response;
    }

    /**
     * @return mixed|null
     * @throws FileMakerException
     */
    private function dataApiLogin()
    {
        $query = [
            'uri' => DataApi::ENDPOINT_BASE . DataApi::ENDPOINT_LOGIN,
            'queryParams' => null,
            'headers' => [
                'Authorization: basic ' . base64_encode($this->username . ':' . $this->password)
            ],
            'method' => 'POST',
            'body' => [
                'fmDataSource' => [
                    [
                        'database' => $this->database,
                        'username' => $this->username,
                        'password' => $this->password,
                    ]
                ]
            ],
            'params' => [
                'version' => 'vLatest',
                'database' => $this->database,
            ],
        ];
        $response = json_decode($this->runDataApiQuery($query), true);
        if ($response['messages'][0]['code'] != 0) {
            return $this->returnOrThrowException($response['messages'][0]['message'], $response['messages'][0]['code']);
        }
        $this->token = $response['response']['token'];
        return $this->token;
    }

    /**
     * @return FileMakerException|bool|null
     * @throws FileMakerException
     */
    public function dataApiLogout()
    {
        if (!$this->token) {
            return true;
        }
        $query = [
            'uri' => DataApi::ENDPOINT_BASE . DataApi::ENDPOINT_LOGOUT,
            'queryParams' => null,
            'headers' => [
                //'Authorization: basic ' . base64_encode($this->username . ':' . $this->password)
            ],
            'method' => 'DELETE',
            'body' => null,
            'params' => [
                'version' => 'vLatest',
                'database' => $this->database,
                'sessionToken' => $this->getSessionBearer(),
            ],
        ];
        $response = json_decode($this->runDataApiQuery($query), true);
        if (@$response['messages'][0]['code'] != 0) {
            return $this->returnOrThrowException($response['messages'][0]['message'], $response['messages'][0]['code']);
        }
        $this->token = null;
        return true;
    }

    /**
     * @param $query
     * @param ((mixed|string|string[][])[]|null|string)[] $query
     *
     * @return FileMakerException|bool|string
     *
     * @throws FileMakerException
     */
    public function runDataApiQuery(array $query)
    {
        $uri = $query['uri'];
        $uri = str_replace('{host}', $this->hostspec, $uri);

        foreach ($query['params'] as $key => $value) {
            $uri = str_replace('{' . $key . '}', rawurlencode($value), $uri);
        }
        $queryParams = $footPrint = [];
        if ($query['queryParams']) {
            foreach ($query['queryParams'] as $option => $value) {
                if (($value !== true) && strtolower($this->getProperty('charset')) !== 'utf-8') {
                    $value = utf8_encode($value);
                } elseif (is_array($value)) {
                    $value = json_encode($value);
                }
                $queryParams[] = rawurlencode($option) . ($value === true ? '' : '=' . rawurlencode($value));
                $footPrint[] = $option . "=" . (preg_match('/\.value$/', $option) ? ":$option" : $value);
            }
            $uri .= "?" . implode('&', $queryParams);
        }

        $curl = curl_init($uri);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $query['method']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        $headers = array_merge([], $query['headers'], [
            'Content-Type: application/json'
        ]);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        if ($curlOptions = $this->getProperty('curlOptions')) {
            foreach ($curlOptions as $key => $value) {
                curl_setopt($curl, $key, $value);
            }
        }
        if ($query['body']) {
            $body = json_encode($query['body']);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        }

        $this->lastRequestedUrl = $uri;

        $this->log("Perform request: " . $this->lastRequestedUrl
            . (isset($body) ? PHP_EOL . 'Body: ' . $body : '')
            , FileMaker::LOG_INFO
        );
        //$this->log('Query Footprint : ' .implode('&', $footPrint), FileMaker::LOG_DEBUG);

        $profileKey = $this->lastRequestedUrl . (isset($body) ? PHP_EOL . 'Body: ' . $body : '');
        $this->beginProfile($profileKey);
        $response = curl_exec($curl);
        $this->endProfile($profileKey);

        //$responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($curlError = curl_errno($curl)) {
            return $this->handleCurlError($curlError, $curl);
        }

        curl_close($curl);

        return $response;
    }

    /**
     * @param bool $renew
     * @return bool|mixed|null
     * @throws FileMakerException
     */
    private function getSessionBearer($renew = false)
    {
        //Clear token in case of renew (current token has expired)
        if ($renew) {
            $this->token = null;
        }

        //return token if we already have it
        if ($this->token) {
            return $this->token;
        }

        $key = md5($this->hostspec . $this->database . $this->username . $this->password);
        if ($renew || !$this->token = $this->sessionGet('bearer-' . $key)) {
            $this->token = $this->dataApiLogin();
            $this->sessionSet('bearer-' . $key, $this->token);
        }
        return $this->token;
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
    public function executeCWP($params, $grammar = 'fmresultset')
    {
        $restParams = $footPrint = [];
        foreach ($params as $option => $value) {
            if (($value !== true) && strtolower($this->getProperty('charset')) !== 'utf-8') {
                $value = utf8_encode($value);
            }
            $restParams[] = urlencode($option) . ($value === true ? '' : '=' . urlencode($value));
            $footPrint[] = $option . "=" . (preg_match('/\.value$/', $option) ? ":$option" : $value);
        }

        $host = $this->getProperty('hostspec');
        if (substr($host, -1, 1) !== '/') {
            $host .= '/';
        }
        $host .= 'fmi/xml/' . $grammar . '.xml';
        //$this->log('Request for ' . $host, FileMaker::LOG_INFO);

        $curl = curl_init($host);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FAILONERROR, true);
        $curlHeadersSent = false;
        if (!headers_sent()) {
            $curlHeadersSent = true;
            curl_setopt($curl, CURLOPT_HEADER, true);
        }
        $this->setCurlWPCSessionCookie($curl);

        if ($this->getProperty('username')) {
            $auth = base64_encode(
                $this->getProperty('username') . ':' . $this->getProperty('password')
            );
            $authHeader = 'Authorization: Basic ' . $auth;
            curl_setopt(
                $curl,
                CURLOPT_HTTPHEADER,
                [
                    'Content-Type: application/x-www-form-urlencoded; charset=utf-8',
                    'X-FMI-PE-ExtendedPrivilege: IrG6U+Rx0F5bLIQCUb9gOw==',
                    $authHeader
                ]
            );
        } else {
            curl_setopt(
                $curl,
                CURLOPT_HTTPHEADER,
                [
                    'Content-Type: application/x-www-form-urlencoded; charset=utf-8',
                    'X-FMI-PE-ExtendedPrivilege: IrG6U+Rx0F5bLIQCUb9gOw=='
                ]
            );
        }

        curl_setopt($curl, CURLOPT_POSTFIELDS, implode('&', $restParams));
        if ($curlOptions = $this->getProperty('curlOptions')) {
            foreach ($curlOptions as $key => $value) {
                curl_setopt($curl, $key, $value);
            }
        }
        $this->lastRequestedUrl = $host . '?' . implode('&', $restParams);

        $this->log("Perform request: " . $this->lastRequestedUrl, FileMaker::LOG_INFO);
        $this->log('Query Footprint : ' . implode('&', $footPrint), FileMaker::LOG_DEBUG);

        $this->beginProfile($this->lastRequestedUrl);
        $curlResponse = curl_exec($curl);
        $this->endProfile($this->lastRequestedUrl);

        if ($curlError = curl_errno($curl)) {
            return $this->handleCurlError($curlError, $curl);
        }

        curl_close($curl);

        $this->setClientWPCSessionCookie($curlResponse);
        if ($curlHeadersSent) {
            $curlResponse = $this->eliminateXMLHeader($curlResponse);
        }
        //$this->log($curlResponse, FileMaker::LOG_DEBUG);

        return $curlResponse;
    }

    /**
     * Returns the fully qualified URL for the specified container field.
     * Pass in a URL string that represents the file path for the container
     * field contents. For example, get the URL for a container field
     * named 'Cover Image'.  For example:
     *
     * @param string $url URL of the container field contents to get.
     *
     * @return string Fully qualified URL to container field contents
     * @example <IMG src="<?php echo $fm->getContainerDataURL($record->getField('Cover Image')); ?>">
     *
     */
    public function getContainerDataURL($url)
    {
        if (!$this->useDataApi) {
            if (strncasecmp($url, '/fmi/xml/cnt', 11) !== 0) {
                $decodedUrl = htmlspecialchars_decode($url);
            } else {
                $decodedUrl = $this->getProperty('hostspec');
                if (substr($decodedUrl, -1, 1) === '/') {
                    $decodedUrl = substr($decodedUrl, 0, -1);
                }
                $decodedUrl .= $url;
                $decodedUrl = htmlspecialchars_decode($decodedUrl);
            }
            return $decodedUrl;
        } else {
            return $url;
        }
    }

    /**
     * Set curl Sesion cookie
     * @param resource $curl a cUrl handle ressource
     */
    private function setCurlWPCSessionCookie($curl)
    {
        if (!$this->getProperty('useCookieSession')) {
            return;
        }
        if (isset($_COOKIE["WPCSessionID"])) {
            $wpcSessionId = $_COOKIE["WPCSessionID"];
            if (!is_null($wpcSessionId)) {
                $header = "WPCSessionID=" . $wpcSessionId;
                curl_setopt($curl, CURLOPT_COOKIE, $header);
            }
        }
    }

    /**
     * Pass WPC session cookie to client for later auth
     * @param string $curlResponse a curl response
     */
    private function setClientWPCSessionCookie($curlResponse)
    {
        if (!$this->getProperty('useCookieSession')) {
            return;
        }
        $found = preg_match('/WPCSessionID="([^;]*)";/m', $curlResponse, $matches);
        /* Update WPCSession Cookie if needed */
        if ($found && @$_COOKIE['WPCSessionID'] !== $matches[1]) {
            setcookie("WPCSessionID", $matches[1]);
            $_COOKIE['WPCSessionID'] = $matches[1];
        }
    }

    /**
     *
     * @param string $curlResponse a curl response
     * @return int content length, -1 if not provided by headers
     */
    private function getContentLength($curlResponse)
    {
        $found = preg_match('/Content-Length: (\d+)/', $curlResponse, $matches);
        if ($found) {
            return $matches[1];
        } else {
            return -1;
        }
    }

    /**
     *
     * @param string $curlResponse a curl response
     * @return string curlResponse without xml header
     */
    private function eliminateXMLHeader($curlResponse)
    {
        $isXml = strpos($curlResponse, "<?xml");
        if ($isXml !== false) {
            return substr($curlResponse, $isXml);
        } else {
            return $curlResponse;
        }
    }

    /**
     *
     * @param string $curlResponse a curl response
     * @return string cUrl Response without leading carriage return
     */
    private function eliminateContainerHeader($curlResponse)
    {
        $len = strlen("\r\n\r\n");
        $pos = strrpos($curlResponse, "\r\n\r\n");
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
     * @return FileMakerException|null
     * @throws FileMakerException
     */
    public function __set($name, $value)
    {
        if (array_key_exists($name, $this->properties)) {
            $this->properties[$name] = $value;
        } else {
            return $this->returnOrThrowException('Attempt to set an unsupported property (' . $name . ')');
        }
        return null;
    }

    /**
     * magic getter
     *
     * @param string $name
     *
     * @return FileMakerException|string
     * @throws FileMakerException
     * @throws ReflectionException
     */
    public function __get($name)
    {
        $getter = 'get' . $name;
        if (array_key_exists($name, $this->properties)) {
            return $this->properties[$name];
        } elseif (method_exists($this, $getter)) {
            //test if it is a valid function (no args)
            $reflection = new ReflectionMethod(__CLASS__, $getter);
            if (sizeof($reflection->getParameters()) === 0 and $reflection->isPublic()) {
                return $this->$getter();
            }
        }

        return $this->returnOrThrowException('Attempt to access an unsupported property (' . $name . ')');
    }

    /**
     * @param array|string $value
     * @return array|string
     */
    public function toOutputCharset($value)
    {
        if (strtolower($this->getProperty('charset')) !== 'iso-8859-1') {
            return $value;
        }
        if (is_array($value)) {
            $output = [];
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
    public function getLastRequestedUrl()
    {
        return $this->lastRequestedUrl;
    }

    /**
     * @param string $value
     * @return string
     */
    public function dateConvertInput($value)
    {
        if ($this->getProperty('dateFormat') === null) {
            return $value;
        }
        try {
            $date = DateTime::createFromFormat($this->getProperty('dateFormat'), $value);
            return $date->format('m/d/Y');
        } catch (Exception $e) {
            $this->log('Could not convert string to a valid DateTime : ' . $e->getMessage(), FileMaker::LOG_ERR);
            return $value;
        }
    }

    /**
     * @param string $value
     * @return string
     */
    public function dateConvertOutput($value)
    {
        if ($this->getProperty('dateFormat') === null) {
            return $value;
        }
        try {
            $date = DateTime::createFromFormat('m/d/Y', $value);
            return $date->format($this->getProperty('dateFormat'));
        } catch (Exception $e) {
            $this->log('Could not convert string to a valid DateTime : ' . $e->getMessage(), FileMaker::LOG_ERR);
            return $value;
        }
    }

    /**
     * Return or throw a FileMakerException according to settings
     * @param string $message
     * @param int $code
     * @param null $previous
     * @return null|FileMakerException $previous
     * @throws FileMakerException
     */
    public function returnOrThrowException($message = null, $code = null, $previous = null)
    {
        $exception = new FileMakerException($this, $message, $code, $previous);
        if ($this->getProperty('errorHandling') == 'exception') {
            throw $exception;
        }
        return $exception;
    }

    /**
     * Convert curl errors to FileMakerException
     * @param int $curlError
     * @param resource $curl
     * @return FileMakerException
     * @throws FileMakerException
     */
    private function handleCurlError($curlError, $curl)
    {
        if ($curlError === 52) {
            return $this->returnOrThrowException(
                'cURL Communication Error: (' . $curlError . ') ' . curl_error($curl)
                . ' - The Web Publishing Core and/or FileMaker Server services are not running.'
            );
        } elseif ($curlError === 22) {
            if (stristr("50", curl_error($curl))) {
                return $this->returnOrThrowException(
                    'cURL Communication Error: (' . $curlError . ') ' . curl_error($curl)
                    . ' - The Web Publishing Core and/or FileMaker Server services are not running.'
                );
            } else {
                return $this->returnOrThrowException(
                    'cURL Communication Error: (' . $curlError . ') ' . curl_error($curl)
                    . ' - This can be due to an invalid username or password, or if the FMPHP privilege is not '
                    . 'enabled for that user.'
                );
            }
        }
        return $this->returnOrThrowException(
            'cURL Communication Error: (' . $curlError . ') ' . curl_error($curl)
        );
    }

    private function connexionId()
    {
        return md5($this->hostspec . "#" . $this->database . "#" . $this->username);
    }
}
