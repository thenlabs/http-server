<?php
declare(strict_types=1);

namespace ThenLabs\HttpServer\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;
use ThenLabs\SocketServer\Connection;

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
     * @var Connection
     */
    protected $connection;

    public function __construct(Request $request, Connection $connection)
    {
        $this->request = $request;
        $this->connection = $connection;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }
}
