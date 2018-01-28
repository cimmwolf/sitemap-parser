<?php
require_once __DIR__ . '/config.php';

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

define('PID', getmypid());
/**
 * @param array $pages
 * @param string $task
 * @param $output OutputInterface
 */
function runProcesses($pages, $task, &$output)
{
    $pdo = new PDO(DSN);
    $pdo->query('DROP TABLE IF EXISTS pages');
    $pdo->query('CREATE TABLE pages (url TEXT NOT NULL, status TEXT, scope INTEGER NOT NULL)');

    for ($i = 0; $i < PROCESSES; $i++) {
        $sliceSize = ceil(count($pages) / PROCESSES);
        $slice = array_slice($pages, $i * $sliceSize, $sliceSize);

        foreach (array_chunk($slice, 999) as $part) {
            $values = implode(',', array_pad([], count($part), "(?,$i)"));
            $stmt = $pdo->prepare("INSERT INTO pages (url, scope) VALUES $values");
            if (!$stmt) {
                fwrite(STDERR, $pdo->errorInfo()[2] . PHP_EOL);
            }
            $stmt->execute($part);
            unset($stmt);
        }

        $cPath = __DIR__ . '/_process.php';
        execInBackground("php $cPath -s $i -p " . PID . " -t $task");
    }

    $do = count($pages);
    $done = 0;
    $progress = new ProgressBar($output, $do);
    $progress->setRedrawFrequency(10);
    $progress->setFormatDefinition('custom', ' %current%/%max% %bar% %message%');
    $progress->setFormat('custom');
    $progress->setMessage('');
    $progress->start();
    while ($done < $do) {
        $done = $pdo->query('SELECT COUNT(status) FROM pages WHERE status IS NOT NULL')->fetchColumn();
        $codes = $pdo->query('SELECT status, COUNT(status) AS count FROM pages WHERE status IS NOT NULL GROUP BY status')->fetchAll(PDO::FETCH_ASSOC);
        $message = [];
        foreach ($codes as $code) {
            $message[] = str_replace(200, 'ok', $code['status']) . ': ' . $code['count'];
        }
        $progress->setMessage(implode(' ', $message));
        $progress->setProgress($done);
        usleep(500000);
    }
    $progress->finish();
}

/**
 * @param $website_url
 * @param $output OutputInterface
 * @return array
 */
function getPages($website_url, &$output)
{
    $output->writeln("  Getting sitemap of $website_url ...");
    $sitemap = simplexml_load_file($website_url . '/sitemap.xml');

    if ($sitemap === false) {
        $output->writeln("Can't get $website_url . /sitemap.xml");
        exit;
    }

    $pages = [];
    foreach ($sitemap as $url) {
        $pages[] = urldecode($url->loc);
    }
    return $pages;
}

/**
 * @param $cmd string
 */
function execInBackground($cmd)
{
    if (substr(php_uname(), 0, 7) == "Windows") {
        pclose(popen("start /B " . $cmd, "r"));
    } else {
        exec($cmd . " > /dev/null 2>/dev/null &");
    }
}


$app = new Silly\Application();

$app->command('check website_url', function ($website_url, OutputInterface $output) {
    $pages = getPages($website_url, $output);
    runProcesses($pages, 'check', $output);
});

$app->command('links website_url', function ($website_url, OutputInterface $output) {
    $pages = getPages($website_url, $output);

    $pdo = new PDO(DSN);
    $pdo->query('DROP TABLE IF EXISTS links');
    $pdo->query('CREATE TABLE links (url TEXT NOT NULL, status TEXT)');
    $pdo->query('CREATE UNIQUE INDEX links_url_uindex ON links (url);');
    unset($pdo);

    runProcesses($pages, 'parse-links', $output);

    $pdo = new PDO(DSN);
//    $pdo->query("DELETE FROM links WHERE url NOT LIKE 'http%'");
    $pages = $pdo->query('SELECT url FROM links')->fetchAll(PDO::FETCH_COLUMN);
    unset($pdo);

    $output->writeln('');
    $output->writeln('  Checking founded links...');

    runProcesses($pages, 'check', $output);
});

$app->command('metadata website_url', function ($website_url, OutputInterface $output) {
    /**
     * @param array $tree
     * @param callable $function
     * @param int $level
     * @param string $path
     */
    function walker($tree, $function, $level = 0, $path = '')
    {
        foreach ($tree as $branchName => $branch) {
            if (isset($branch['_self'])) {
                $function($branch['_self'], $level, $path);
                unset($branch['_self']);
            }
            if (count($branch) > 0)
                walker($branch, $function, $level + 1, "$path/$branchName");
        }
    }

    $output->writeln("  Getting sitemap of $website_url ...");
    $sitemap = simplexml_load_file($website_url . '/sitemap.xml');

    $resultFile = __DIR__ . '/runtime/' . parse_url($website_url, PHP_URL_HOST) . '-metadata.csv';

    $paths = [];
    foreach ($sitemap as $url) {
        $paths[] = parse_url($url->loc, PHP_URL_PATH);
    }
    natsort($paths);

    $tree = [];
    foreach ($paths as $path) {
        $levels = explode('/', $path);
        $temp = &$tree;
        foreach ($levels as $key => $level) {
            // в условии неочевидное преобразование для анализа ссылки на главную страницу
            if (!empty($level) OR (empty(array_filter($levels)) AND $level = '/')) {
                if (!isset($temp[$level]))
                    $temp[$level] = [];

                if ($key == (count($levels) - 1))
                    $temp[$level]['_self'] = ['path' => $path];

                $temp = &$temp[$level];
            }
        }
    }
    unset($temp);

    file_put_contents($resultFile, 'URL, Title, Keywords, Description, "Build Time: ' . date('r') . '"' . PHP_EOL);
    $previous = '';
    $progress = new \cli\progress\Bar(' Getting meta data', count($paths), 1000);
    walker($tree, function (&$self, $level, $path) use ($website_url, &$previous, $resultFile, &$progress) {
        $data = [];
        $page = file_get_contents($website_url . $self['path']);
        preg_match('~<title>(.*?)</title>~', $page, $temp);
        $data[] = $temp[1] ?? '';
        preg_match('~<meta name="keywords" content="(.*?)">~', $page, $temp);
        $data[] = $temp[1] ?? '';
        preg_match('~<meta name="description" content="(.*?)">~', $page, $temp);
        $data[] = $temp[1] ?? '';

        if ($data == $previous) {
            foreach ($data as &$item)
                $item = '--//--';
        } else
            $previous = $data;

        $row = [];
        $row[] = $website_url . $self['path'];
        $row = array_merge($row, $data);

        foreach ($row as &$item)
            $item = '"' . $item . '"';

        $line = implode(',', $row) . PHP_EOL;
        file_put_contents($resultFile, $line, FILE_APPEND);
        $progress->tick();
    });

    $progress->finish();
});

/** @noinspection PhpUnhandledExceptionInspection */
$app->run();