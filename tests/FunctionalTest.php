<?php

namespace ThenLabs\HttpServer\Tests;

use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;

setTestCaseNamespace(__NAMESPACE__);
setTestCaseClass(TestCase::class);

testCase('FunctionalTest.php', function () {
    test(function () {
        $logsFile = __DIR__.'/.logs/test.logs';
        file_put_contents($logsFile, '');

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

        $driver->close();

        $this->assertTrue(true);
    });
});
