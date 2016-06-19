# FileMaker PHP-API
FileMaker PHP API rewrited for PHP 5.5+

## Installation

### Using Composer
You can use the `composer` package manager to install. Either run:

    $ php composer.phar require airmoi/filemaker "*"

or add:

    "airmoi/filemaker": "*"

to your composer.json file

### Manual Install

You can also manually install the API easily to your project. Just download the source [ZIP](https://github.com/airmoi/FileMaker/archive/master.zip) and extract its content into your project.

## Usage

STEP 1 : include the API autoload

*This step is facultative if you have installed the API using composer*

STEP2 : Create a FileMaker instance
```php
use airmoi\FileMaker\FileMaker;

$fm = new FileMaker($database, $host, $username, $password);
```

STEP3 : Read the 'Important Notice' below

STEP4 : use it quite the same way you would use the offical API...

...And enjoy code completion using your favorite IDE

You may also find sample usage by reading the `sampletest.php` file located in the tests folder 

## Important notice

This version of the PHP-API differs a bit from the official package provided by FileMaker, you may not be able to switch form the official API to this version without upgrading your code.

The major changes compared to the official package are : 

* All actions that may return an error object now throws FileMakerException or FileMAkerValidationException instead 
* Logging is not supported (i'm stile looking for a better/more generic way to implement it)
* there is no more 'conf.php' file (all properties are stored in the main `FileMaker.php` file)
  
