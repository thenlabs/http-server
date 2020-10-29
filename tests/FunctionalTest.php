<?php

namespace ThenLabs\HttpServer\Tests;

use ThenLabs\HttpServer\HttpServer;

setTestCaseNamespace(__NAMESPACE__);
setTestCaseClass(TestCase::class);

testCase('FunctionalTest.php', function () {
    test(function () {
        $server = new HttpServer($_ENV['HOSTNAME'], $_ENV['PORT']);
    });
});
