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
            $this->assertEquals('1.1', $request->getProtocolVersion());

            $expectedHeaders = [
                'host' => ['localhost'],
                'user-agent' => ['Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:82.0) Gecko/20100101 Firefox/82.0'],
                'accept' => ['text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8'],
                'accept-language' => ['es-ES,es;q=0.8,en-US;q=0.5,en;q=0.3'],
                'accept-encoding' => ['gzip, deflate'],
                'connection' => ['keep-alive'],
                'upgrade-insecure-requests' => ['1'],
                'if-modified-since' => ['Tue, 08 Sep 2020 14:25:41 GMT'],
                'if-none-match' => ['"2aa6-5aece1c1ae8c3-gzip"'],
                'cache-control' => ['max-age=0'],
            ];

            $this->assertArraySubset($expectedHeaders, $request->headers->all());
        });

        test(function () {
            $message = <<<HTTP
                POST /cgi-bin/process.cgi/?q=1&vendor=thenlabs HTTP/1.1
                User-Agent: Mozilla/4.0 (compatible; MSIE5.01; Windows NT)
                Host: www.tutorialspoint.com
                Content-Type: application/x-www-form-urlencoded
                Content-Length: length
                Accept-Language: en-us
                Accept-Encoding: gzip, deflate
                Connection: Keep-Alive\n\rlicenseID=string&content=string&paramsXML=string
            HTTP;

            $request = Utils::createRequestFromHttpMessage($message);

            $this->assertEquals('POST', $request->getMethod());
            $this->assertEquals('/cgi-bin/process.cgi/', $request->getPathInfo());
            $this->assertEquals('1.1', $request->getProtocolVersion());

            $expectedHeaders = [
                'user-agent' => ['Mozilla/4.0 (compatible; MSIE5.01; Windows NT)'],
                'host' => ['www.tutorialspoint.com'],
                'content-type' => ['application/x-www-form-urlencoded'],
                'content-length' => ['length'],
                'accept-language' => ['en-us'],
                'accept-encoding' => ['gzip, deflate'],
                'connection' => ['Keep-Alive'],
            ];

            $this->assertArraySubset($expectedHeaders, $request->headers->all());

            $this->assertEquals(
                'licenseID=string&content=string&paramsXML=string',
                $request->getContent()
            );

            $this->assertEquals('1', $request->query->get('q'));
            $this->assertEquals('thenlabs', $request->query->get('vendor'));

            $this->assertEquals('string', $request->request->get('licenseID'));
        });
    });
});
