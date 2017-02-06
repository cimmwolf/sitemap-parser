<?php

if (php_sapi_name() != 'cli') {
    die('Must run from command line');
}

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);
ini_set('log_errors', 0);
ini_set('html_errors', 0);

require_once __DIR__ . '/vendor/autoload.php';


$commands = ['checkurls', 'metadata'];

if (!in_array($argv[1], $commands)) {
    echo 'Wrong command.' . PHP_EOL;
    exit;
}

$strict = in_array('--strict', $_SERVER['argv']);
$arguments = new \cli\Arguments(compact('strict'));
$arguments->addFlag(['help', 'h'], 'Show this help screen');

$arguments->addOption(['site', 's'], [
    'description' => 'Site URL']);

$arguments->parse();

if ($arguments['help']) {
    echo $arguments->getHelpScreen();
    echo "\n\n";
    exit;
}

\cli\line(" Getting sitemap of %s...", $arguments['site']);

$sitemap = simplexml_load_file($arguments['site'] . '/sitemap.xml');

if ($sitemap === false) {
    \cli\err('Error');
    exit;
}

require_once __DIR__ . '/commands/' . $argv[1] . '.php';