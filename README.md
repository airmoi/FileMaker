# FileMaker® PHP-API
FileMaker® PHP API rewritten for PHP 5.5+.
It is compatible with PHP 7.0+ and uses PSR-4 autoloading specifications.

## Features
This version of the PHP-API add the following feature to the offical API :
* Error handling using Exception (you can restore the original behavior using option 'errorHandling' => 'default')
* PSR-4 autoloading and installation using composer
* PHP 7.0+ compatibility
* 'dateFormat' option to select the input/output date format
* 'emptyAsNull' option to return empty value as null
* Support setRange() method with PerformScript command (as supported by CWP)
* A method to get the url of your last CWP call: `$fm->getLastRequestedUrl()`
* A method to check if a findRequest is empty: `$request->isEmpty()`
* A method to get the value list associated to a field from a Record: `$record->getValueListTwoField('my_field')`
* 'useDateFormatInRequests' allow you to use defined 'dateFormat' in request (support wildcards and range)
* Use custom "logger" : your logger must implement a log($message, $level) method. Additionally, your logger may implement profileBegin($key)/profileEnd($key) methods to profile query performances.
* Set a Cache object (must implement set($key, $value) and get($key) methods) to cache meta data such as layouts and scripts and reduce call
* dataAPI support : set 'engine' property to "dataPI" to switch from CWP to dataAPI (see dataAPI support section).
* Add a "session" Object to sessionHandler property to enable session level data storage (save dataAPI token to users session to reduce dataAPI login/logout). Your sessionHandler must implement set($key, $value) and get($key) methods

## Requirements

* PHP >= 7.1
* (optional) PHPUnit to run tests.

## Installation

### Using Composer
You can use the `composer` package manager to install. Either run:

    $ php composer.phar require airmoi/filemaker "*"

or add:

    "airmoi/filemaker": "^3.0"

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

...And enjoy code completion using your favorite IDE and php 7 support without notice/warnings.

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
To keep a dataAPI session alive across a "user" session and reduce login/logout operations, you may use a sessionHandler. It will enable PHP-API to save the token into the user session and reuse it as long as it is valid. If no session handler is defined, dataAPI will automatically logout when FileMaker's object is destroyed (ie end of your script)
4. When a token is expired, it will automatically be regenerated
5. DataAPI's "Layout" method only returns fields and value lists of the layout. Other meta's such as layout OT, layout Base Table name (same for portals) are only returned with a found set (hope Claris will fix this in a next release). It means, in order to keep the "getLayout" method consistent, that its has to query a "random" record on the given layout to retrieve those meta, so don't be surpised if you use getLayout() to see 2 queries performed to dataAPI. If the table is empty, layout Object won't have those metas.
6. DataAPI as a default pagination of 100 (while CWP does not have default pagination at all). To prevent truncated results, when no range limit is defined, the API will loop across pages to return the full foundset 

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
