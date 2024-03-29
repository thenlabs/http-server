<?php
declare(strict_types=1);

namespace ThenLabs\HttpServer;

use Exception;
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
use ThenLabs\SocketServer\Connection;
use ThenLabs\SocketServer\Event\DataEvent;
use ThenLabs\SocketServer\SocketServer;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class HttpServer extends SocketServer
{
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
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 80;

        $config['socket'] = "tcp://{$host}:{$port}";
        $config['logger_name'] = $config['logger_name'] ?? 'thenlabs_http_server';
        $config['log_messages'] = [
            'server_started' => 'Server started in %SOCKET%',
            'server_stopped' => 'Server stopped.',
            'new_connection' => null,
            'disconnection'  => null,
        ];

        parent::__construct($config);

        $this->routes = new RouteCollection();
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

    /**
     * Add a route accesible only by the GET method.
     *
     * @param string $path
     * @param callable $callback
     * @return void
     */
    public function get(string $path, callable $callback): void
    {
        $routeName = uniqid('route');

        $route = new Route($path, ['_controller' => $callback]);
        $route->setMethods(['GET']);

        $this->addRoute($routeName, $route);
    }

    /**
     * Add a route accesible only by the POST method.
     *
     * @param string $path
     * @param callable $callback
     * @return void
     */
    public function post(string $path, callable $callback): void
    {
        $routeName = uniqid('route');

        $route = new Route($path, ['_controller' => $callback]);
        $route->setMethods(['POST']);

        $this->addRoute($routeName, $route);
    }

    /**
     * Add a route accesible only by the PUT method.
     *
     * @param string $path
     * @param callable $callback
     * @return void
     */
    public function put(string $path, callable $callback): void
    {
        $routeName = uniqid('route');

        $route = new Route($path, ['_controller' => $callback]);
        $route->setMethods(['PUT']);

        $this->addRoute($routeName, $route);
    }

    /**
     * Add a route accesible only by the PATCH method.
     *
     * @param string $path
     * @param callable $callback
     * @return void
     */
    public function patch(string $path, callable $callback): void
    {
        $routeName = uniqid('route');

        $route = new Route($path, ['_controller' => $callback]);
        $route->setMethods(['PATCH']);

        $this->addRoute($routeName, $route);
    }

    /**
     * Add a route accesible only by the DELETE method.
     *
     * @param string $path
     * @param callable $callback
     * @return void
     */
    public function delete(string $path, callable $callback): void
    {
        $routeName = uniqid('route');

        $route = new Route($path, ['_controller' => $callback]);
        $route->setMethods(['DELETE']);

        $this->addRoute($routeName, $route);
    }

    /**
     * Add a route accesible only by the OPTIONS method.
     *
     * @param string $path
     * @param callable $callback
     * @return void
     */
    public function options(string $path, callable $callback): void
    {
        $routeName = uniqid('route');

        $route = new Route($path, ['_controller' => $callback]);
        $route->setMethods(['OPTIONS']);

        $this->addRoute($routeName, $route);
    }

    public function onData(DataEvent $event): void
    {
        $data = $event->getData();
        $request = Utils::createRequestFromHttpMessage($data);

        if (! $request instanceof Request) {
            return;
        }

        $this->handleRequest($request, $event->getConnection());
    }

    protected function handleRequest(Request $request, Connection $connection): void
    {
        $requestEvent = new RequestEvent($request, $connection);
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

                $this->sendResponse($response, $connection);
                return;
            }
        }

        $requestContext = new RequestContext();
        $matcher = new UrlMatcher($this->routes, $requestContext);

        try {
            $parameters = $matcher->matchRequest($request);
        } catch (ResourceNotFoundException $exception) {
            $response = new Response('', 404);
            $this->sendResponse($response, $connection);
            return;
        }

        if (! isset($parameters['_controller']) ||
            ! is_callable($parameters['_controller'])
        ) {
            $response = new Response('', 404);
            $this->sendResponse($response, $connection);
            return;
        }

        $response = call_user_func($parameters['_controller'], $request, $parameters);

        if (! $response instanceof Response) {
            throw new Exception\InvalidResponseException($parameters['_route']);
        }

        $this->sendResponse($response, $connection);
        return;
    }

    protected function sendResponse(Response $response, Connection $connection): void
    {
        $responseEvent = new ResponseEvent($response, $connection);
        $this->dispatcher->dispatch($responseEvent);
        $response = $responseEvent->getResponse();

        $connection->write((string) $response);

        $method = $this->currentRequest->getMethod();
        $uri = Utils::getRequestUri($this->currentRequest);

        $statusCode = $response->getStatusCode();
        $status = Response::$statusTexts[$statusCode];

        $this->logger->debug("{$method}:{$uri}...{$status}({$statusCode})");

        $connection->close();

        $this->currentRequest = null;
    }
}
