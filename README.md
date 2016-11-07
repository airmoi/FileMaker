# FileMaker® PHP-API
FileMaker® PHP API rewrited for PHP 5.5+

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

*This step is facultative if you are using composer*

STEP2 : Create a FileMaker instance
```php
use airmoi\FileMaker\FileMaker;

$fm = new FileMaker($database, $host, $username, $password, $options);
```

STEP3 : Read the 'Important Notice' below

STEP4 : use it quite the same way you would use the offical API...

...And enjoy code completion using your favorite IDE

You may also find sample usage by reading the `simpletest.php` file located in the "demo" folder 

## Important notice

The 2.1 release aims to improve compatibility with the original FileMaker PHP-API.
However, you will need to changes few things in your code in order to use it

The major changes compared to the official package are : 

* Call autoloader.php instead of FileMaker.php to load the API
* API now support Exceptions error handling, you may switch between those behaviors by changing property 'errorHandeling' to 'default' or 'exception' (default value is 'exception')
* There is no more 'conf.php' use "setProperty" to define specifics API's settings. You may also use an array of properties on FileMaker instanciation, ie : new FileMaker( $db, $host, $user, $pass, ['property' => 'value'])

You can use the offical [PHP-API guide](https://fmhelp.filemaker.com/docs/14/fr/fms14_cwp_guide.pdf) provided by FieMaker® for everything else.
