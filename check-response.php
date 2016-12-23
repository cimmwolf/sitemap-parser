<?php
$resultFile = __DIR__ . '/runtime/result.log';
$opts = getopt('u:');

$headers = get_headers($opts['u']);
if ($headers !== false) {
    preg_match('~\d{3}~', $headers[0], $matches);
    $httpCode = $matches[0];

    file_put_contents($resultFile, "$httpCode: {$opts['u']}" . PHP_EOL, FILE_APPEND);
}