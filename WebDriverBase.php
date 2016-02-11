<?php

namespace jumper423;

use Facebook\WebDriver\Firefox\FirefoxDriver;
use Facebook\WebDriver\Firefox\FirefoxProfile;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\WebDriverCapabilityType;
use Facebook\WebDriver\Exception\UnknownServerException;
use yii\base\Model;

class WebDriverBase extends Model
{
    public $sessionId;

    public $proxy = [];

    /**
     * @var RemoteWebDriver
     */
    protected $driver;

    protected $host = 'http://127.0.0.1:4444/wd/hub';

    public function __construct($config = [])
    {
        parent::__construct($config);

        $getAllSessions = RemoteWebDriver::getAllSessions($this->host);
        if ($this->sessionId && ArrayHelper::inMultiArray($this->sessionId, $getAllSessions, 'id')) {
            $this->driver = RemoteWebDriver::createBySessionID($this->sessionId, $this->host);
        } else {
            $desired_capabilities = DesiredCapabilities::firefox();
            $fp = new FirefoxProfile();
            $desired_capabilities->setCapability(WebDriverCapabilityType::NATIVE_EVENTS, true);
            if (isset($this->proxy['ip'])) {
                $this->proxy['port'] = ArrayHelper::getValue($this->proxy, 'port');
                $fp->setPreference('network.proxy.ssl_port', $this->proxy['port']);
                $fp->setPreference('network.proxy.ssl', $this->proxy['ip']);
                $fp->setPreference('network.proxy.http_port', $this->proxy['port']);
                $fp->setPreference('network.proxy.http', $this->proxy['ip']);
                $fp->setPreference('network.proxy.type', 1);
            }
            $desired_capabilities->setCapability(FirefoxDriver::PROFILE, $fp);
            $this->driver = RemoteWebDriver::create($this->host, $desired_capabilities, 600000, 600000);
            $this->sessionId = $this->driver->getSessionID();
        }
    }

    protected static function scriptElementVisibility($element = '$(\'body\')'){
        $script = "{$element}.css({
            visibility:'visible',
            height:'30px',
            width:'300px',
            background:'black',
            position:'static',
            opacity:'1',
        })";
        return $script;
    }

    public function quit()
    {
        try {
            $this->driver->quit();
        } catch (UnknownServerException $e){}
    }
}