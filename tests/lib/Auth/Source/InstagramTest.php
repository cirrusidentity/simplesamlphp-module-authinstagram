<?php

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

class Test_sspmod_authinstagram_Auth_Source_Instagram extends PHPUnit_Framework_TestCase {

    public $host = 'http://localhost:4444/wd/hub'; // this is the default

    public $module_config;

    public static function setUpBeforeClass() {
        putenv('SIMPLESAMLPHP_CONFIG_DIR=' . dirname(dirname(dirname(__DIR__))) . '/config');
    }

    protected function setUp() {
        $this->module_config = SimpleSAML_Configuration::getConfig('module_authinstagram.php');
    }

    public function createChromeDriver() {
        $capabilities = DesiredCapabilities::chrome();
        $options = new ChromeOptions();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);
        return RemoteWebDriver::create($this->host, $capabilities, 5000);
    }

    public function testLogin() {
        $driver = $this->createChromeDriver();

        $url = $this->module_config->getString('hostURL') . '/module.php/core/authenticate.php?as=authinstagram';

        $driver->get($url);

        $driver->wait()->until(
            WebDriverExpectedCondition::urlMatches('~^https://www.instagram.com/accounts/login.*$~')
        );

        $driver->findElement(WebDriverBy::id('id_username'))->sendKeys($this->module_config->getString('username'));
        $driver->findElement(WebDriverBy::id('id_password'))->sendKeys($this->module_config->getString('password'));
        $driver->findElement(WebDriverBy::id('login-form'))->submit();

        $urlRegexp = preg_quote('@' . $url . '@');

        $driver->wait()->until(
            WebDriverExpectedCondition::urlMatches($urlRegexp)
        );

        // TODO check attributes

        // $driver->quit();
    }


}