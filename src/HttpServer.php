<?php
declare(strict_types=1);

namespace ThenLabs\HttpServer;

use ThenLabs\HttpServer\Event\RequestEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Mime\MimeTypes;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
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
        'blocking' => true,
        'backlog' => 100,
        'document_root' => null,
    ];

    protected $config;

    protected $socket;

    protected $dispatcher;

    protected $logger;

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
                Closure::fromCallable([$this, 'defaultListener']),
                -1
            );
        }

        $this->logger = new Logger('http_server');
        $this->logger->pushHandler(new StreamHandler(STDOUT));
    }

    public function getSocket()
    {
        return $this->socket;
    }

    public function getLogger(): Logger
    {
        return $this->logger;
    }

    public function setLogger(Logger $logger): void
    {
        $this->logger = $logger;
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

        $this->logger->info("Server Started in http://{$this->config['host']}:{$this->config['port']}");
    }

    public function stop(): void
    {
        socket_close($this->socket);

        $this->logger->info("Server Stopped.");
    }

    public function run(): void
    {
        if (! $clientSocket = socket_accept($this->socket)) {
            return;
        }

        if (! $httpRequestMessage = socket_read($clientSocket, 1500, PHP_BINARY_READ)) {
            return;
        }

        $request = Utils::createRequestFromHttpMessage($httpRequestMessage);

        if (! $request instanceof Request) {
            return;
        }

        $response = new Response;
        $requestEvent = new RequestEvent($request, $response, $clientSocket);

        $this->dispatcher->dispatch($requestEvent);

        $responseMessage = (string) $response;

        @socket_write($clientSocket, $responseMessage, strlen($responseMessage));

        $method = $request->getMethod();
        $uri = $requestEvent->getRequestUri();
        $status = Response::$statusTexts[$response->getStatusCode()];

        $this->logger->info("{$method}:{$uri}...{$status}");

        @socket_close($clientSocket);
    }

    protected function defaultListener(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        $filePath = $event->getRequestUri();

        if ($filePath == '/') {
            $filePath = '/index.html';
        }

        $fileName = $this->config['document_root'].$filePath;

        if (file_exists($fileName)) {
            $fileInfo = pathinfo($fileName);

            $mimeTypes = new MimeTypes();
            $mimeTypes = $mimeTypes->getMimeTypes($fileInfo['extension']);

            if (! empty($mimeTypes)) {
                $mimeType = $mimeTypes[0];
                $response->headers->set('Content-Type', $mimeType);
            }

            $response->setContent(file_get_contents($fileName));
        } else {
            $response->setStatusCode(404);
        }
    }
}
