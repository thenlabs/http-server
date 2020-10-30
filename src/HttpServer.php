<?php
declare(strict_types=1);

namespace ThenLabs\HttpServer;

use ThenLabs\HttpServer\Event\RequestEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Exception;
use Closure;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class HttpServer
{
    protected $defaultConfig = [
        'host' => '127.0.0.1',
        'port' => 80,
        'blocking' => false,
        'backlog' => 0,
        'document_root' => null,
    ];

    protected $config;

    protected $socket;

    protected $dispatcher;

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->defaultConfig, $config);

        $this->socket = socket_create(AF_INET, SOCK_STREAM, getprotobyname('tcp'));
        $this->dispatcher = new EventDispatcher;

        if (isset($this->config['document_root']) &&
            is_dir($this->config['document_root'])
        ) {
            $this->dispatcher->addListener(
                RequestEvent::class,
                Closure::fromCallable([$this, 'serveFileListener'])
            );
        }
    }

    public function getSocket()
    {
        return $this->socket;
    }

    public function getDispatcher(): EventDispatcher
    {
        return $this->dispatcher;
    }

    public function start(): void
    {
        if (! @socket_bind($this->socket, $this->config['host'], $this->config['port'])) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);

            throw new Exception($errormsg);
        }

        socket_listen($this->socket, $this->config['backlog']);

        if (! $this->config['blocking']) {
            socket_set_nonblock($this->socket);
        }
    }

    public function run(): void
    {
        $clientSocket = socket_accept($this->socket);

        if (! $clientSocket) {
            return;
        }

        $httpRequestMessage = socket_read($clientSocket, 1500, PHP_BINARY_READ);
        $request = Utils::createRequestFromHttpMessage($httpRequestMessage);

        if (! $request instanceof Request) {
            return;
        }

        $response = new Response;
        $requestEvent = new RequestEvent($request, $response);

        $this->dispatcher->dispatch($requestEvent);

        if (! $response = $requestEvent->getResponse()) {
            $response = new Response('', 404);
        }

        $responseMessage = (string) $response;

        socket_write($clientSocket, $responseMessage, strlen($responseMessage));
        socket_close($clientSocket);
    }

    private function serveFileListener(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $uri = $request->getRequestUri();

        if ('?%0A=' == substr($uri, -5)) {
            $filePath = substr($uri, 0, -5);
        }

        $fileName = $this->config['document_root'].$filePath;

        if (file_exists($fileName)) {
            $response = $event->getResponse();
            $response->setContent(file_get_contents($fileName));
        }
    }
}
