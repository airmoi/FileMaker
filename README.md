# FileMaker® PHP-API
FileMaker® PHP API rewritten for PHP 5.5+.
It is compatible with PHP 7.0+ and uses PSR-4 autoloading specifications.

## Features
This version of the PHP-API add the following feature to the offical API :
* Error handling using Exception (you can restore the original behavior using option 'errorHandeling' => 'default')
* PSR-4 autoloading and installation using composer
* PHP 7.0+ compatibility
* 'dateFormat' option to select the input/output date format (not compatible with find requets yet)
* 'emptyAsNull' option to return empty value as null
* Support setRange() method with PerformScript command (as supported by CWP)
* A method to get the url of your last CWP call: `$fm->getLastRequestedUrl()`
* A method to check if a findRequest is empty: `$request->isEmpty()`
* A method to get the value list associated to a field from a Record: `$record->getValueListTwoField('my_field')`

## Requirements

* PHP >= 5.5
* (optional) PHPUnit to run tests.

## Installation

### Using Composer
You can use the `composer` package manager to install. Either run:

    $ php composer.phar require airmoi/filemaker "*"

or add:

    "airmoi/filemaker": "~2.1.0"

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

## Important notice

The 2.1 release aims to improve compatibility with the original FileMaker PHP-API.
However, you will need to changes few things in your code in order to use it

The major changes compared to the official package are : 

* Call autoloader.php instead of FileMaker.php to load the API
* API now support Exceptions error handling, you may switch between those behaviors by changing property 'errorHandeling' to 'default' or 'exception' (default value is 'exception')
* There is no more 'conf.php' use "setProperty" to define specifics API's settings. You may also use an array of properties on FileMaker instanciation, ie : new FileMaker( $db, $host, $user, $pass, ['property' => 'value'])

You can use the offical [PHP-API guide](https://fmhelp.filemaker.com/docs/14/fr/fms14_cwp_guide.pdf) provided by FileMaker® for everything else.

## TODO
* Finish PHPunit test
* Add functionnal tests
* Support dateFormat option in requests
* Improve parsers
* Add new parsers
* Documentation

## License
FileMaker PHP API is licensed under the BSD License - see the LICENSE file for detail

## Credits

### Contributors

- Thanks to [Matthias Kühne](https://github.com/MatthiasKuehneEllerhold) for PSR-4 implementation and code doc fixes.
