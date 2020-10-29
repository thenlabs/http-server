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
        $messageParts = explode("\n\r", $message);

        if (count($messageParts) > 1) {
            [$messageHeader, $messageContent] = $messageParts;
        } else {
            $messageHeader = $messageParts[0];
        }

        $firstLine = substr($messageHeader, 0, strpos($messageHeader, "\n"));
        $firstLine = trim($firstLine);
        [$method, $uri, $protocolVersion] = explode(' ', $firstLine);

        $headersPattern = <<<'TXT'
        /([\w-]+):([\w \.\/\\\(\);:,\-\+=\*"']+)/
        TXT;

        preg_match_all($headersPattern, $messageHeader, $matchedHeaders);

        $server = [];

        foreach ($matchedHeaders[0] as $key => $value) {
            $headerName = 'HTTP_'.strtoupper(trim($matchedHeaders[1][$key]));
            $headerValue = trim($matchedHeaders[2][$key]);

            $server[$headerName] = $headerValue;
        }

        return Request::create($uri, $method, [], [], [], $server, $messageContent ?? null);

        // $request = new Request;

        // foreach ($matchedHeaders[0] as $key => $value) {
        //     $headerName = trim($matchedHeaders[1][$key]);
        //     $headerValue = trim($matchedHeaders[2][$key]);

        //     $request->headers->set($headerName, $headerValue);
        // }

        // return $request;
    }
}
