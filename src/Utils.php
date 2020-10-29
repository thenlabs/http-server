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
        $headersPattern = <<<'TXT'
        /([\w-]+):([\w \.\/\\\(\);:,\-\+=\*"']+)/
        TXT;

        preg_match_all($headersPattern, $message, $matchedHeaders);

        $request = new Request;

        foreach ($matchedHeaders[0] as $key => $value) {
            $headerName = trim($matchedHeaders[1][$key]);
            $headerValue = trim($matchedHeaders[2][$key]);

            $request->headers->set($headerName, $headerValue);
        }

        return $request;
    }
}
