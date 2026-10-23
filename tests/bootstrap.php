<?php
// ensure we get report on all possible php errors
error_reporting(-1);
require_once __DIR__ . '/../autoloader.php';
require_once __DIR__ . '/../vendor/autoload.php';

// Integration settings may be provided through environment variables (ie: CI secrets),
// they take precedence over <php><var> values of the phpunit configuration file
foreach ([
    'FM_HOST' => 'DB_HOST',
    'FM_USER' => 'DB_USER',
    'FM_PASSWORD' => 'DB_PASSWD',
    'FM_DATABASE' => 'DB_FILE',
    'FM_USE_DATA_API' => 'USE_DATA_API',
] as $env => $global) {
    $value = getenv($env);
    if ($value !== false && $value !== '') {
        $GLOBALS[$global] = $value;
    }
}
$GLOBALS['OFFICIAL_API'] = $GLOBALS['OFFICIAL_API'] ?? 0;
$GLOBALS['USE_DATA_API'] = $GLOBALS['USE_DATA_API'] ?? false;
