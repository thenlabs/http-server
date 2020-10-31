<?php

require __DIR__.'/../vendor/autoload.php';

use ThenLabs\HttpServer\HttpServer;
use ThenLabs\HttpServer\Event\RequestEvent;
use Symfony\Component\HttpFoundation\Response;
use Monolog\Handler\StreamHandler;

$config = [
    'host' => $argv[1] ?? '127.0.0.1',
    'port' => $argv[2] ?? 8080,
    'document_root' => __DIR__.'/document_root',
];

$server = new HttpServer($config);
$server->getLogger()->pushHandler(new StreamHandler(__DIR__.'/.logs/test.logs'));
$server->getDispatcher()->addListener(RequestEvent::class, function ($event) {
    if ($event->getRequestUri() == '/custom') {
        $clientSocket = $event->getClientSocket();

        $response = new Response(<<<HTML
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Document</title>
            </head>
            <body>
                <button>Button</button>
            </body>
            </html>
        HTML);

        $text = (string) $response;

        socket_write($clientSocket, $text, strlen($text));
        socket_close($clientSocket);

        $event->stopPropagation();
    }
});

$server->start();

while (true) {
    $server->run();
}
