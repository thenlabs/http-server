<?php
declare(strict_types=1);

namespace ThenLabs\HttpServer;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Exception;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class HttpServer
{
    protected $socket;

    protected $defaultConfig = [
        'host' => '127.0.0.1',
        'port' => 80,
        'blocking' => true,
        'backlog' => 0,
    ];

    protected $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->defaultConfig, $config);

        $this->socket = socket_create(AF_INET, SOCK_STREAM, getprotobyname('tcp'));
    }

    public function getSocket()
    {
        return $this->socket;
    }

    public function start(): void
    {
        if (! @socket_bind($this->socket, $this->config['host'], $this->config['port'])) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);

            throw new Exception($errormsg);
        }

        socket_listen($this->socket, $this->config['backlog']);

        if ($this->config['blocking']) {
            socket_set_nonblock($this->socket);
        }
    }

    public function run(): void
    {
        $clientSocket = socket_accept($this->socket);

        if (! $clientSocket) {
            return;
        }

        $httpRequestMessage = socket_read($clientSocket, 1024, PHP_BINARY_READ);
        $request = Utils::createRequestFromHttpMessage($httpRequestMessage);

        if (! $request instanceof Request) {
            return;
        }

        $response = $this->handle($request);
        $responseMessage = (string) $response;

        socket_write($clientSocket, $responseMessage, strlen($responseMessage));
        socket_close($clientSocket);
    }

    protected function handle(Request $request): Response
    {
    }
}
