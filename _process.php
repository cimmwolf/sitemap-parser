<?php
require_once __DIR__ . '/config.php';

$opts = getopt('s:p:t:');

$scope = $opts['s'];
$pPID = $opts['p'] ?? null;
$task = $opts['t'] ?? 'check';

$pdo = new PDO(DSN);

$stm = $pdo->prepare('SELECT url FROM pages WHERE scope=:scope');
$stm->execute([':scope' => $scope]);
$items = $stm->fetchAll(PDO::FETCH_COLUMN);
unset($stm);

$ch = curl_init();
if ($task == 'check') {
    curl_setopt($ch, CURLOPT_NOBODY, true);
} else if ($task == 'parse-links') {
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
}
foreach ($items as $item) {
    curl_setopt($ch, CURLOPT_URL, $item);
    $httpCode = 'ERR';
    if ($content = curl_exec($ch)) {
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode == 501 && $task == 'check') {
            curl_setopt($ch, CURLOPT_NOBODY, false);
            if ($content = curl_exec($ch)) {
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            }
            curl_setopt($ch, CURLOPT_NOBODY, true);
        }
    }

    $pdo->setAttribute(PDO::ATTR_TIMEOUT, 100);
    $stm = $pdo->prepare('UPDATE pages SET status=:status WHERE url=:url');
    $stm->execute([':status' => $httpCode, ':url' => $item]);
    unset($stm);

    if ($task == 'parse-links') {
        $urlInfo = parse_url($item);

        preg_match_all('~<a[^>]+href=[\'"]([^\'"]+)[\'"]~imu', $content, $matches);
        $links = array_unique($matches[1]);
        $links = array_filter($links, function ($value) {
            return $value != '/' && substr($value, 0, 1) != '#' && substr($value, 0, 7) != 'mailto:' && substr($value, 0, 4) != 'tel:';
        });
        foreach ($links as &$link) {
            if (substr($link, 0, 2) == '//') {
                $link = $urlInfo['scheme'] . ':' . $link;
            } elseif (substr($link, 0, 1) == '/') {
                $link = $urlInfo['scheme'] . '://' . $urlInfo['host'] . $link;
            }
            $link = explode('#', $link)[0];
            $link = urldecode($link);
        }
        $links = array_unique($links);
        $links = array_filter($links, function ($value) use ($pdo) {
            $stm = $pdo->prepare('SELECT COUNT(url) FROM pages WHERE url=:url');
            $stm->execute([':url' => $value]);
            $result = $stm->fetchColumn();
            return $result == 0;
        });
        if (!empty($links)) {
            $links = array_values($links);

            $pdo->setAttribute(PDO::ATTR_TIMEOUT, 100);
            $stm = $pdo->prepare('INSERT OR IGNORE INTO links (url) VALUES ' . implode(',', array_pad([], count($links), '(?)')));
            if ($stm == false) {
                fwrite(STDERR, $pdo->errorInfo()[2] . PHP_EOL);
                exit;
            }
            $stm->execute($links);
            unset($stm);
        }
    }

    if ($pPID && !isRunning($pPID)) {
        exit(1);
    }
}

unset($pdo);
curl_close($ch);

function isRunning($pid)
{
    if (function_exists('posix_kill')) {
        return posix_kill($pid, 0);
    }
    exec('ps -W -p ' . $pid, $out);
    return count($out) > 1;
}
