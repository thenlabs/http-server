<?php

require __DIR__.'/../../vendor/autoload.php';

use ThenLabs\HttpServer\HttpServer;

$config = [
    'host' => $argv[1] ?? '127.0.0.1',
    'port' => $argv[2] ?? 8080,
    'document_root' => getcwd(),
];

$server = new HttpServer($config);
$server->start();

while (true) {
    $server->run();
}
