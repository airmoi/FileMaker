<?php
/**
 * FileMaker API for PHP configuration file.
 *
 * All settings are in the $__FM_CONFIG array to maintain a
 * clean global namespace.
 */

/**
 * The default character encoding ('UTF-8' or 'ISO-8859-1', case matters).
 */
$__FM_CONFIG['charset'] = 'UTF-8';

/**
 * The default locale for providing string translations of error
 * codes. Options are: 'en'
 */
$__FM_CONFIG['locale'] = 'en';

/**
 * The log level, if a logging object is provided.
 */
$__FM_CONFIG['logLevel'] = FILEMAKER_LOG_ERR;

/**
 * The default hostspec (http://127.0.0.1:80, for example). Avoid using http://localhost:80/
 * as it can cause peformance loss. DO NOT include /fmi/xml in this string.
 */
$__FM_CONFIG['hostspec'] = 'http://127.0.0.1';

/**
 * Specify any additional curl options - SSL certificates, etc. - in
 * an associative array, with curl option names as the keys, and
 * option values as the values.
 */
// $__FM_CONFIG['curlOptions'] = array(CURLOPT_SSL_VERIFYPEER => false);

/**
 * The PHP class to use for representing Records
 */
$__FM_CONFIG['recordClass'] = 'FileMaker_Record';

/**
 * Do pre-validation (validate in PHP engine) on Record data?
 */
$__FM_CONFIG['prevalidate'] = false;
