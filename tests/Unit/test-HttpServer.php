<?php

use ThenLabs\HttpServer\HttpServer;

setTestCaseClass('ThenLabs\HttpServer\Tests\TestCase');

testCase(function () {
    $methods = ['get', 'post', 'put', 'patch', 'delete', 'options'];

    foreach ($methods as $method) {
        test("->{$method}()", function () use ($method) {
            $path = uniqid('/');
            $callback = function () {
            };
            $server = new HttpServer();

            // act
            $server->{$method}($path, $callback);

            $routes = $server->getRoutes()->all();
            $this->assertCount(1, $routes);

            $route = array_values($routes)[0];

            $this->assertEquals([strtoupper($method)], $route->getMethods());
            $this->assertSame($callback, $route->getDefault('_controller'));
        });
    }
});
