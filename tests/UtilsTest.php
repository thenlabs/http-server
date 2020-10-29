<?php

namespace ThenLabs\HttpServer\Tests;

use ThenLabs\HttpServer\Utils;

setTestCaseNamespace(__NAMESPACE__);
setTestCaseClass(TestCase::class);

testCase('UtilsTest.php', function () {
    testCase('#createRequestFromHttpMessage()', function () {
        test(function () {
            $message = <<<HTTP
                GET / HTTP/1.1
                Host: localhost
                User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:82.0) Gecko/20100101 Firefox/82.0
                Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8
                Accept-Language: es-ES,es;q=0.8,en-US;q=0.5,en;q=0.3
                Accept-Encoding: gzip, deflate
                Connection: keep-alive
                Upgrade-Insecure-Requests: 1
                If-Modified-Since: Tue, 08 Sep 2020 14:25:41 GMT
                If-None-Match: "2aa6-5aece1c1ae8c3-gzip"
                Cache-Control: max-age=0
            HTTP;

            $request = Utils::createRequestFromHttpMessage($message);

            $this->assertEquals('GET', $request->getMethod());
            $this->assertEquals('/', $request->getPathInfo());
            // $this->assertEquals('HTTP/1.1', $request->getProtocolVersion());

            $expectedHeaders = [
                'Host' => 'localhost',
                'User-Agent' => 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:82.0) Gecko/20100101 Firefox/82.0',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'es-ES,es;q=0.8,en-US;q=0.5,en;q=0.3',
                'Accept-Encoding' => 'gzip, deflate',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
                'If-Modified-Since' => 'Tue, 08 Sep 2020 14:25:41 GMT',
                'If-None-Match' => '"2aa6-5aece1c1ae8c3-gzip"',
                'Cache-Control' => 'max-age=0',
            ];

            $this->assertEquals($expectedHeaders, $request->headers->all());
        });
    });
});
