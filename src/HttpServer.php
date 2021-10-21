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

    public function onData(DataEvent $event): void
    {
        $data = $event->getData();
        $request = Utils::createRequestFromHttpMessage($data);

        if (! $request instanceof Request) {
            return;
        }

        $clientSocket = $event->getConnection()->getSocket();

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
