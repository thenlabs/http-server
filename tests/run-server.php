<?php

require __DIR__.'/../vendor/autoload.php';

use ThenLabs\HttpServer\HttpServer;
use Monolog\Handler\StreamHandler;

$config = [
    'host' => $argv[1] ?? '127.0.0.1',
    'port' => $argv[2] ?? 8080,
    'document_root' => __DIR__.'/document_root',
];

$server = new HttpServer($config);
$server->getLogger()->pushHandler(new StreamHandler(__DIR__.'/.logs/test.logs'));
$server->start();

while (true) {
    $server->run();
}
