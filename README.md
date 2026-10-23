# FileMaker® PHP-API
FileMaker® PHP API rewritten for modern PHP.
It requires PHP 8.2+ (tested up to PHP 8.5) and uses PSR-4 autoloading specifications.

## Features
This version of the PHP-API add the following feature to the offical API :
* Error handling using Exception (you can restore the original behavior using option 'errorHandling' => 'default')
* PSR-4 autoloading and installation using composer
* PHP 8.2+ compatibility (no deprecation notices up to PHP 8.5)
* 'dateFormat' option to select the input/output date format
* 'emptyAsNull' option to return empty value as null
* Support setRange() method with PerformScript command (as supported by CWP)
* A method to get the url of your last CWP call: `$fm->getLastRequestedUrl()`
* A method to check if a findRequest is empty: `$request->isEmpty()`
* A method to get the value list associated to a field from a Record: `$record->getValueListTwoField('my_field')`
* 'useDateFormatInRequests' allow you to use defined 'dateFormat' in request (support wildcards and range)
* Use custom "logger" : your logger must implement a log($message, $level) method. Additionally, your logger may implement profileBegin($key)/profileEnd($key) methods to profile query performances.
* Set a Cache object (must implement set($key, $value) and get($key) methods) to cache meta data such as layouts and scripts and reduce call
* dataAPI support : set 'useDataApi' property to true to switch from CWP (XML) to dataAPI (see dataAPI support section).
* Add a "session" Object to sessionHandler property to enable session level data storage (save dataAPI token to users session to reduce dataAPI login/logout). Your sessionHandler must implement set($key, $value) and get($key) methods
* 'skipAutoEnteredValidation' option to skip prevalidation of empty auto-entered fields (ie: serial primary keys) when creating records

## Requirements

* PHP >= 8.2 with the `curl`, `json`, `mbstring` and `xml` extensions
* A FileMaker Server with either XML Web Publishing (CWP) or the Data API enabled, reachable from your web server
* (optional) PHPUnit to run tests.

The API is pure PHP: it runs on any operating system and hosting supported by PHP (Linux, shared hosting/cPanel, macOS, Windows...),
it does not need to be installed on the FileMaker Server machine. On shared hosting, make sure the required PHP extensions are enabled
and that outgoing HTTP(S) connections to your FileMaker Server are allowed.

PHP 5.x/7.x users should stick to the 3.0.x releases.

## Installation

### Using Composer
You can use the `composer` package manager to install. Either run:

    $ php composer.phar require airmoi/filemaker "*"

or add:

    "airmoi/filemaker": "^3.1"

to your composer.json file

### Manual Install

You can also manually install the API easily to your project. Just download the source [ZIP](https://github.com/airmoi/FileMaker/archive/master.zip) and extract its content into your project.

## Usage

STEP 1 : Read the 'Important Notice' below

STEP 2 : include the API autoload

```php
require '/path/to/autoloader.php';
```
*This step is facultative if you are using composer*

STEP 3 : Create a FileMaker instance
```php
use airmoi\FileMaker\FileMaker;

$fm = new FileMaker($database, $host, $username, $password, $options);
```

STEP 4 : use it quite the same way you would use the offical API...

...And enjoy code completion using your favorite IDE and PHP 8 support without notice/warnings.

You may also find sample usage by reading the `sample.php` file located in the "demo" folder 

### Sample demo code

```php
use airmoi\FileMaker\FileMaker;
use airmoi\FileMaker\FileMakerException;

require('/path/to/autoloader.php');

$fm = new FileMaker('database', 'localhost', 'filemaker', 'filemaker', ['prevalidate' => true]);

try {
    $command = $fm->newFindCommand('layout_name');
    $records = $command->execute()->getRecords(); 
    
    foreach($records as $record) {
        echo $record->getField('fieldname');
        ...
    }
} 
catch (FileMakerException $e) {
    echo 'An error occured ' . $e->getMessage() . ' - Code : ' . $e->getCode();
}
```

## Important notices

### Switch from original PHP-API

The 2.1 release aims to improve compatibility with the original FileMaker PHP-API.
However, you will need to changes few things in your code in order to use it

The major changes compared to the official package are : 

* Call autoloader.php instead of FileMaker.php to load the API
* API now support Exceptions error handling, you may switch between those behaviors by changing property 'errorHandling' to 'default' or 'exception' (default value is 'exception')
* There is no more 'conf.php' use "setProperty" to define specifics API's settings. You may also use an array of properties on FileMaker instanciation, ie : new FileMaker( $db, $host, $user, $pass, ['property' => 'value'])
* All constants are now part of the FileMaker class, use FileMaker::<CONSTANT_NAME> instead of <CONSTANT_NAME>
* Also notice that FILEMAKER_SORT_ASCEND/DESCEND have been renamed to FileMaker::SORT_ASCEND/FileMaker::SORT_DESCEND

You can use the offical [PHP-API guide](https://fmhelp.filemaker.com/docs/14/fr/fms14_cwp_guide.pdf) provided by FileMaker® for everything else.

### dataAPI support

A hard work has been done to make dataAPI support as transparent as possible. The goal was to let you be able to switch from CWP to dataAPI by just switching a property.

However, despite the dataAPI has roughly the same functionality, some of its behaviors differs from the CWP, which required some workarounds.
1. Globals lives across a session and can only be defined using a dedicated method, to fix that and prevent unexpected behaviors, globals are defined before performing a query, then reset after the query was performed.
2. Perform Script action returns the script result instead of a foundset (no workaround here, just to inform you that you'll get a script result instead of the resulting foundset).
As a workaround, you may create a find query and use setScript($scriptName, $scriptParam) method to get the resulting foundset
3. DataAPI requires to login using credentials, then perform queries using the resulting token.
The token is saved and reused across requests, as long as it is valid, using your sessionHandler if defined, or the PHP session otherwise (a PHP session is started if needed and headers are not sent yet).
When the token cannot be saved (CLI scripts, headers already sent...), the API automatically logs out when the FileMaker object is destroyed (ie end of your script), so FileMaker Server connections are released.
Saved tokens expire on FileMaker Server after 15 minutes of inactivity; call `$fm->logout()` to close the Data API session explicitly (ie: when your user logs out).
4. When a token is expired, it will automatically be regenerated
5. DataAPI's "Layout" method only returns fields and value lists of the layout. Other meta's such as layout OT, layout Base Table name (same for portals) are only returned with a found set (hope Claris will fix this in a next release). It means, in order to keep the "getLayout" method consistent, that its has to query a "random" record on the given layout to retrieve those meta, so don't be surpised if you use getLayout() to see 2 queries performed to dataAPI. If the table is empty, layout Object won't have those metas.
6. DataAPI as a default pagination of 100 (while CWP does not have default pagination at all). To prevent truncated results, when no range limit is defined, the API will loop across pages to return the full foundset 

### Prevalidation

When the 'prevalidate' option is enabled, field validation rules defined in FileMaker (not empty, numeric only, max characters, date/time formats...) are checked before sending the request.
* Add commands and new records validate every field of the layout
* Edit commands and existing records only validate the fields being modified
* Set 'skipAutoEnteredValidation' to true to ignore empty auto-entered fields (ie: serial primary keys with a "not empty" rule) when creating records

### Logging

Use `$fm->setLogger($logger)` to log requests, your logger must implement a `log($message, $level)` method. Only messages whose level is lower than or equal to the 'logLevel' property are forwarded to your logger
(default to `FileMaker::LOG_ERR`, set `FileMaker::LOG_INFO` to log requests or `FileMaker::LOG_DEBUG` for more details, `false` to disable logging). Nothing is logged when no logger is set.
Passwords and Data API tokens are masked in logged requests.

### Troubleshooting

* *"FileMaker Server did not return an XML document"* (previously *"XML error: Not well-formed (invalid token) at line 1"*): the XML Web Publishing Engine answered with something else than XML.
  Make sure XML Web Publishing is enabled in FileMaker Server Admin Console and that `https://<your server>/fmi/xml/fmresultset.xml` returns an XML document in your browser.
  If FileMaker Server redirects HTTP requests to HTTPS, use an `https://` hostspec. Alternatively, switch to the Data API (`'useDataApi' => true`).
* *"Authorization header missing 'Basic' or 'Bearer'"* using the Data API: upgrade to 3.1.0 or later.

## Changelog

### 3.1.0
* Requires PHP 8.2+, fix PHP 8.1 to 8.5 deprecations (`utf8_encode/decode`, `curl_close`, `xml_parser_free`, `xml_set_object`, null passed to internal functions) (#48, #52, #57)
* Data API: fix authorization headers (#54)
* Data API: tokens are now saved and reused, unsaved tokens are released on destruct (fixes error 812 "Exceeded host's capacity"), add `logout()` (#56)
* `setSessionHandler()` and `setCache()` were not applied
* Cached layouts are bound to the requesting FileMaker instance and cached per engine (records could be committed through another instance/engine), credentials are no longer serialized into external caches
* Fix `Field::hasValidationRule()` always returning false
* Explicit error messages when FileMaker Server does not return XML or redirects the request (#50)
* Logging: passwords and tokens are masked, logLevel is compared as integer (#42)
* Fix double date conversion when committing a new record with a custom 'dateFormat' (#35)
* Prevalidation only checks modified fields on edit (#19), new 'skipAutoEnteredValidation' option (#36)
* Fix missing leading zeros in dates converted by 'useDateFormatInRequests'
* Tests migrated to PHPUnit 10, offline test suite: `vendor/bin/phpunit --testsuite unit`

## Tests

```bash
composer install
vendor/bin/phpunit --testsuite unit          # offline tests
cp phpunit.sample.xml phpunit.xml            # set your FileMaker Server credentials
vendor/bin/phpunit --testsuite integration   # requires tests/php-api-test.fmp12 (unzip tests/php-api-test.fmp12.zip) hosted on FileMaker Server
```

## TODO
* Finish PHPunit test
* Add functionnal tests
* Improve parsers
* ~~Add new parsers~~
* ~~Add support for dataAPI~~
* Documentation

## License
FileMaker PHP API is licensed under the BSD License - see the LICENSE file for detail

## Credits

### Contributors

- Thanks to [Matthias Kühne](https://github.com/MatthiasKuehneEllerhold) for PSR-4 implementation and code doc fixes.
- Thanks to [jeremiahsmall](https://github.com/jeremiahsmall) for improving error handling.
