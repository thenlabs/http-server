<?php
declare(strict_types=1);

namespace ThenLabs\HttpServer\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class RequestEvent extends Event
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var resource
     */
    protected $clientSocket;

    public function __construct(Request $request, $clientSocket)
    {
        $this->request = $request;
        $this->clientSocket = $clientSocket;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getClientSocket()
    {
        return $this->clientSocket;
    }
}
