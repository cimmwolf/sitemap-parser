<?php
$resultFile = __DIR__ . '/runtime/result.log';
if (!file_exists(dirname($resultFile)))
    mkdir(dirname($resultFile));
file_put_contents($resultFile, '');

function cecho($string, $newLine = true)
{
    $string = " $string";

    if ($newLine) {
        $string .= PHP_EOL;
    }
    if (!empty($_SESSION['lastOutput']) AND strpos($_SESSION['lastOutput'], PHP_EOL) === false)
        echo str_repeat(chr(8), strlen($_SESSION['lastOutput']));

    $_SESSION['lastOutput'] = $string;
    echo $string;
}

function execInBackground($cmd)
{
    if (substr(php_uname(), 0, 7) == "Windows") {
        pclose(popen("start /B " . $cmd, "r"));
    } else {
        exec($cmd . " > /dev/null 2>/dev/null &");
    }
}

$opts = getopt('s:');

cecho("Getting sitemap of {$opts['s']}...");

$sitemap = simplexml_load_file($opts['s'] . '/sitemap.xml');

if ($sitemap === false) {
    cecho('Error');
    exit;
}

cecho("Checking {$sitemap->count()} links:");
$errors = [];
foreach ($sitemap as $url) {
    execInBackground("php check-response.php -u $url->loc");

    usleep(500000);

    $result = [];
    $tmp = [];
    $data = file_get_contents($resultFile);
    $data = explode(PHP_EOL, $data);
    $data = array_filter($data);
    foreach ($data as $row) {
        $row = explode(': ', $row);
        if (!empty($row)) {
            if (empty($result[$row[0]]))
                $result[$row[0]] = 0;
            $result[$row[0]]++;
            if ($row[0] != 200)
                $tmp[] = $row;
        }
    }

    foreach (array_slice($tmp, count($errors)) as $error)
        cecho("{$error[0]}: {$error[1]}");
    $errors = $tmp;

    $output = [];
    foreach ($result as $code => $count) {
        $output[] = "$code: $count";
    }
    cecho(implode(' ', $output), false);
}

