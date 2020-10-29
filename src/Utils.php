<?php
declare(strict_types=1);

namespace ThenLabs\HttpServer;

use Symfony\Component\HttpFoundation\Request;

/**
 * @author Andy Daniel Navarro Taño <andaniel05@gmail.com>
 */
class Utils
{
    public static function createRequestFromHttpMessage(string $message): Request
    {
        return new Request;
    }
}