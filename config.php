<?php
if (php_sapi_name() != 'cli') {
    die('Must run from command line');
}

require_once __DIR__.'/vendor/autoload.php';

define('PROCESSES', 5);
define('DSN', 'sqlite:' . __DIR__ . '/runtime/db.sqlite');