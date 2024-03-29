<?php

require __DIR__.'/../../vendor/autoload.php';

use Monolog\Handler\StreamHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ThenLabs\HttpServer\HttpServer;

$config = [
    'host' => $argv[1] ?? '127.0.0.1',
    'port' => $argv[2] ?? 8080,
    'document_root' => __DIR__.'/document_root',
    'loop_delay' => 100000, // the default value is 1 but causes conflicts with xdebug.
];

$server = new HttpServer($config);
$server->getLogger()->pushHandler(new StreamHandler(__DIR__.'/.logs/test.logs'));

$server->get('/custom/{id}', function (Request $request, array $parameters): Response {
    $id = $parameters['id'];
    $title = $request->query->get('title');

    return new Response('
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>'.$title.'</title>
        </head>
        <body>
            <button data-id="'.$id.'">Button</button>
        </body>
        </html>
    ');
});

$server->start();
