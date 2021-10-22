<?php

namespace ThenLabs\HttpServer\Tests;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Symfony\Component\Process\Process;

setTestCaseClass('ThenLabs\HttpServer\Tests\TestCase');

testCase(function () {
    staticProperty('serverProcess');

    setUpBeforeClassOnce(function () {
        // empty the logs file.
        file_put_contents(LOGS_FILE, '');

        static::$serverProcess = new Process(
            [
            'php',
            'tests/Functional/run-server.php',
            $_ENV['HOST'],
            $_ENV['PORT']]
        );

        static::$serverProcess->start();

        sleep(1);
    });

    test(function () {
        $logsFileName = __DIR__.'/.logs/test.logs';
        file_put_contents($logsFileName, '');

        $capabilities = DesiredCapabilities::chrome();
        $driver = RemoteWebDriver::create($_ENV['SELENIUM_SERVER'], $capabilities);

        $driver->get("http://{$_ENV['HOST']}:{$_ENV['PORT']}");

        $driver->wait()->until(
            WebDriverExpectedCondition::alertIsPresent(),
            'DOM is ready'
        );
        $driver->switchTo()->alert()->accept();

        do {
            $readyState = $driver->executeScript('return document.readyState');
        } while ($readyState != 'complete');

        $id = mt_rand(1, 10);
        $title = uniqid();

        $driver->get("http://{$_ENV['HOST']}:{$_ENV['PORT']}/custom/{$id}?title={$title}");

        $buttons = $driver->findElements(WebDriverBy::cssSelector('button[data-id="'.$id.'"]'));

        $this->assertEquals($title, $driver->getTitle());
        $this->assertCount(1, $buttons);

        $logsFileContent = file_get_contents($logsFileName);

        $expectedLines = [
            'GET:/...OK',
            'GET:/css/styles.css...OK',
            'GET:/css/unexistent-file.css...Not Found',
            'GET:/js/scripts.js...OK',
            'GET:/img/image.gif...OK',
            'GET:/img/image.png...OK',
            'GET:/img/image.jpeg...OK',
            'GET:/favicon.ico...OK',
            "GET:/custom/{$id}?title={$title}...OK",
        ];

        foreach ($expectedLines as $line) {
            $this->assertStringContainsString($line, $logsFileContent);
        }

        $driver->close();
    });

    tearDownAfterClassOnce(function () {
        static::$serverProcess->stop();
    });
});
