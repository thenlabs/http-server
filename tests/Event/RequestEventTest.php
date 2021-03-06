<?php

namespace ThenLabs\HttpServer\Tests;

use ThenLabs\HttpServer\Event\RequestEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

setTestCaseNamespace(__NAMESPACE__);
setTestCaseClass(TestCase::class);

testCase('RequestEventTest.php', function () {
    testCase('#getRequestUri()', function () {
        test(function () {
            $uri = 'http://localhost/'.uniqid('path');

            $request = $this->createMock(Request::class);
            $request->method('getRequestUri')->willReturn($uri.'?%0A=');

            $response = $this->createMock(Response::class);

            $event = new RequestEvent($request, $response, null);

            $this->assertEquals($uri, $event->getRequestUri());
        });
    });
});
