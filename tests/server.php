<?php

require __DIR__.'/../vendor/autoload.php';

use ThenLabs\HttpServer\HttpServer;

$host = $argv[1] ?? '127.0.0.1';
$port = $argv[2] ?? 8080;

$server = new HttpServer($host, $port);
$server->start();

while (true) {
    $server->run();
}
