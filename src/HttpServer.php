<?php
declare(strict_types=1);

namespace ThenLabs\HttpServer;

use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use ThenLabs\HttpServer\Event\RequestEvent;
use ThenLabs\HttpServer\Event\ResponseEvent;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class HttpServer
{
    /**
     * @var array
     */
    protected $defaultConfig = [
        'host' => '0.0.0.0',
        'port' => 80,
        'blocking' => true,
        'document_root' => null,
        'logger_name' => 'thenlabs_http_server',
        'log_messages' => [
            'server_started' => 'Server Started in http://%HOST%:%PORT%',
            'server_stopped' => 'Server Stopped.',
        ],
        'timeout' => -1,
        'fread_length' => 1500,
    ];

    /**
     * @var array
     */
    protected $config;

    /**
     * @var resource
     */
    protected $socket;

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var RouteCollection
     */
    protected $routes;

    /**
     * @var Request
     */
    protected $currentRequest;

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->defaultConfig, $config);
        $this->dispatcher = new EventDispatcher();
        $this->routes = new RouteCollection();

        $this->logger = new Logger($this->config['logger_name']);
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

    public function getRoutes(): RouteCollection
    {
        return $this->routes;
    }

    public function setRoutes(RouteCollection $routes): void
    {
        $this->routes = $routes;
    }

    public function addRoute(string $name, Route $route): void
    {
        $this->routes->add($name, $route);
    }

    public function getDispatcher(): EventDispatcher
    {
        return $this->dispatcher;
    }

    protected function createSocket(string $address)
    {
        $socket = stream_socket_server($address, $errorCode, $errorMessage);

        if (! $socket) {
            throw new Exception\HttpServerException($errorMessage, $errorCode);
        }

        return $socket;
    }

    protected function getLogMessage(string $key, array $parameters = []): ?string
    {
        if (! isset($this->config['log_messages'][$key]) ||
            ! is_string($this->config['log_messages'][$key])
        ) {
            return null;
        }

        return str_replace(
            array_keys($parameters),
            array_values($parameters),
            $this->config['log_messages'][$key]
        );
    }

    public function start(): void
    {
        $this->socket = $this->createSocket("tcp://{$this->config['host']}:{$this->config['port']}");

        if (! $this->config['blocking']) {
            stream_set_blocking($this->socket, false);
        }

        $this->logger->info($this->getLogMessage('server_started', [
            '%HOST%' => $this->config['host'],
            '%PORT%' => $this->config['port'],
        ]));
    }

    public function stop(): void
    {
        fclose($this->socket);

        $this->logger->info($this->getLogMessage('server_stopped'));
    }

    public function run(): void
    {
        if (! $clientSocket = stream_socket_accept($this->socket, $this->config['timeout'])) {
            return;
        }

        if (! $httpRequestMessage = fread($clientSocket, $this->config['fread_length'])) {
            return;
        }

        $request = Utils::createRequestFromHttpMessage($httpRequestMessage);

        if (! $request instanceof Request) {
            return;
        }

        $this->handleRequest($request, $clientSocket);
    }

    protected function handleRequest(Request $request, $clientSocket): void
    {
        $requestEvent = new RequestEvent($request, $clientSocket);
        $this->dispatcher->dispatch($requestEvent);
        $request = $requestEvent->getRequest();

        $this->currentRequest = $request;

        if (isset($this->config['document_root']) &&
            is_dir($this->config['document_root'])
        ) {
            $response = new Response();
            $filePath = Utils::getRequestUri($request);

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

                $this->sendResponse($response, $clientSocket);
                return;
            }
        }

        $requestContext = new RequestContext();
        $matcher = new UrlMatcher($this->routes, $requestContext);

        try {
            $parameters = $matcher->matchRequest($request);
        } catch (ResourceNotFoundException $exception) {
            $response = new Response('', 404);
            $this->sendResponse($response, $clientSocket);
            return;
        }

        if (! isset($parameters['_controller']) ||
            ! is_callable($parameters['_controller'])
        ) {
            $response = new Response('', 404);
            $this->sendResponse($response, $clientSocket);
            return;
        }

        $response = call_user_func($parameters['_controller'], $request, $parameters);

        if (! $response instanceof Response) {
            throw new Exception\InvalidResponseException($parameters['_route']);
        }

        $this->sendResponse($response, $clientSocket);
        return;
    }

    protected function sendResponse(Response $response, $clientSocket): void
    {
        $responseEvent = new ResponseEvent($response, $clientSocket);
        $this->dispatcher->dispatch($responseEvent);
        $response = $responseEvent->getResponse();

        $responseMessage = (string) $response;

        fwrite($clientSocket, $responseMessage, strlen($responseMessage));

        $method = $this->currentRequest->getMethod();
        $uri = Utils::getRequestUri($this->currentRequest);
        $status = Response::$statusTexts[$response->getStatusCode()];

        $this->logger->info("{$method}:{$uri}...{$status}");

        fclose($clientSocket);
        $this->currentRequest = null;
    }
}
