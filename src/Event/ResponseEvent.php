<?php
declare(strict_types=1);

namespace ThenLabs\HttpServer\Event;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class ResponseEvent extends Event
{
    /**
     * @var Response
     */
    protected $response;

    /**
     * @var resource
     */
    protected $clientSocket;

    public function __construct(Response $response, $clientSocket)
    {
        $this->response = $response;
        $this->clientSocket = $clientSocket;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function getClientSocket()
    {
        return $this->clientSocket;
    }
}
