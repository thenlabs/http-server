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
        $request = new Request;

        preg_match_all('/([\w-]+):([\w \.\/\\\(\);:,\-\+=\*"]+)/', $message, $matches);

        foreach ($matches[0] as $key => $value) {
            $headerName = trim($matches[1][$key]);
            $headerValue = trim($matches[2][$key]);

            $request->headers->set($headerName, $headerValue);
        }

        return $request;
    }
}
