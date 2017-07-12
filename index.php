<?php

ini_set('memory_limit', '512M');
if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}
require __DIR__ . '/vendor/autoload.php';

session_start();

if(isset($argv))
{
    if($argv!=null)
    {
        array_shift($argv);
        $pathInfo = '/' . implode('/', $argv);
        $environment = \Yee\Environment::mock([
            'REQUEST_METHOD' => "GET",
            'PATH_INFO' => $pathInfo,
            'REQUEST_URI' => $pathInfo,
        ]);
    }
}


$config = require __DIR__ . '/config.php';


$app = new \Yee\Yee($config);

$app->view(new \Yee\Views\Twig());

new \Yee\Managers\RoutingCacheManager(
    array(
        'cache' => __DIR__ . '/cache/routing',
        'controller' => array( __DIR__ . '/App/Controllers' )
    )
);
new \Yee\Managers\DatabaseManager();

$app->execute();
