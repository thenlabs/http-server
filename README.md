
# HttpServer

A HTTP server written in PHP with help of the Symfony Components.

>If you like this project gift us a ⭐.

## Installation.

    $ composer require thenlabs/http-server

## Usage.

Create a file with the next example content:

>You should change the content according to your needs.

```php
<?php
// run-server.php

require __DIR__.'/vendor/autoload.php';

use ThenLabs\HttpServer\HttpServer;

$config = [
    'host' => '127.0.0.1',
    'port' => 8080,
    'document_root' => __DIR__.'/vendor/thenlabs/http-server/tests/Functional/document_root',
];

$server = new HttpServer($config);
$server->start();

while (true) {
    $server->run();
}
```

This file should be executed like that:

    $ php run-server.php

Once does it, we can navigate to the URL and we will see the respectively page.

>In our example, we are serving the `index.html` file which is stored within the `tests/document_root` directory.

![](demo.jpg)

### Creating custom routes.

The HttpServer use the [Symfony Routing Component](https://github.com/symfony/routing) for handle the routing, therefore, you can use all the his possibilities.

The next example shown the way to creating a custom route which only can be access by a GET request.

```php
<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// ...

$server->get('/article/{id}', function (Request $request, array $parameters): Response {
    return new Response("This is the article {$parameters['id']}");
});

// ...
```

### Using the logs.

Like you can will verify, by default are will shows in the console the result of all server requests.

![](console-logs.png)

The logs are created with help of the popular [Monolog](https://github.com/Seldaek/monolog) library.

The next example showns a way to put the logs in a file.

```php
<?php

// ...
use Monolog\Handler\StreamHandler;

// ...
$server->getLogger()->pushHandler(new StreamHandler('/path/to/file.logs'));
```

## Development.

### Running the tests.

Start the selenium server.

    $ java -jar path/to/selenium-server-standalone-x.y.z.jar

Run PHPUnit.

    $ ./vendor/bin/pyramidal
