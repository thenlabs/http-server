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
            $messageContent = null;
        }

        $firstLine = substr($messageHeader, 0, strpos($messageHeader, "\n"));
        $firstLine = trim($firstLine);

        [$method, $uri, $protocolVersion] = explode(' ', $firstLine);

        $headersPattern = <<<'TXT'
        /([\w-]+):([\w \.\/\\\(\);:,\-\+=\*"']+)/
        TXT;

        preg_match_all($headersPattern, $messageHeader, $matchedHeaders);

        $protocolVersionParts = explode('/', $protocolVersion);

        $server = [
            'SERVER_PROTOCOL' => $protocolVersionParts[1],
        ];

        foreach ($matchedHeaders[0] as $key => $value) {
            $headerName = 'HTTP_'.strtoupper(trim($matchedHeaders[1][$key]));
            $headerValue = trim($matchedHeaders[2][$key]);

            $server[$headerName] = $headerValue;
        }

        $parameters = [];

        if ($messageContent) {
            $messageContent = trim($messageContent);
            parse_str($messageContent, $parameters);
        }

        return Request::create($uri, $method, $parameters, [], [], $server, $messageContent);
    }

    public static function getRequestUri(Request $request): string
    {
        $uri = $request->getRequestUri();

        if ('?%0A=' === substr($uri, -5)) {
            $uri = substr($uri, 0, -5);
        }

        return $uri;
    }
}
