<?php
/**
 * @var $arguments
 */

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

$resultFile = __DIR__ . '/../runtime/' . parse_url($arguments['site'], PHP_URL_HOST) . '-metadata.csv';

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
walker($tree, function (&$self, $level, $path) use ($arguments, &$previous, $resultFile, &$progress) {
    $data = [];
    $page = file_get_contents($arguments['site'] . $self['path']);
    preg_match('~<title>(.*?)</title>~', $page, $temp);
    $data[] = $temp[1];
    preg_match('~<meta name="keywords" content="(.*?)">~', $page, $temp);
    $data[] = $temp[1];
    preg_match('~<meta name="description" content="(.*?)">~', $page, $temp);
    $data[] = $temp[1];

    if ($data == $previous) {
        foreach ($data as &$item)
            $item = '--//--';
    } else
        $previous = $data;

    $row = [];
    $row[] = $arguments['site'] . $self['path'];
    $row = array_merge($row, $data);

    foreach ($row as &$item)
        $item = '"' . $item . '"';

    $line = implode(',', $row) . PHP_EOL;
    file_put_contents($resultFile, $line, FILE_APPEND);
    $progress->tick();
});

$progress->finish();