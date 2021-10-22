<?php
declare(strict_types=1);

namespace ThenLabs\HttpServer\Event;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\Event;
use ThenLabs\SocketServer\Connection;

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
     * @var Connection
     */
    protected $connection;

    public function __construct(Response $response, Connection $connection)
    {
        $this->response = $response;
        $this->connection = $connection;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }
}
