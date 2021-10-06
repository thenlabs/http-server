<?php
declare(strict_types=1);

namespace ThenLabs\HttpServer\Exception;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class InvalidResponseException extends HttpServerException
{
    public function __construct(string $routeName)
    {
        parent::__construct("The controller of the route '{$routeName}' do not responds with an instance of 'Symfony\Component\HttpFoundation\Response'.");
    }
}
