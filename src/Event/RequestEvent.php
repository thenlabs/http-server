<?php
declare(strict_types=1);

namespace ThenLabs\HttpServer\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\EventDispatcher\Event;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class RequestEvent extends Event
{
    protected $request;

    protected $response;

    protected $clientSocket;

    public function __construct(Request $request, Response $response, $clientSocket)
    {
        $this->request = $request;
        $this->response = $response;
        $this->clientSocket = $clientSocket;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function setResponse(Response $response): void
    {
        $this->response = $response;
    }

    public function getRequestUri(): string
    {
        $uri = $this->request->getRequestUri();

        if ('?%0A=' === substr($uri, -5)) {
            $uri = substr($uri, 0, -5);
        }

        return $uri;
    }

    public function getClientSocket()
    {
        return $this->clientSocket;
    }
}
