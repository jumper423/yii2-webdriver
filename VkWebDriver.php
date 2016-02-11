<?php

namespace jumper423;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use yii\base\Exception;

class VkWebDriver extends WebDriverBase
{
    public $login;
    public $password;

    /**
     * Получить токен для полного доступа
     * @param $url string
     * @param $recursion bool
     */
    public function getToken($url, $recursion = true)
    {
        $this->driver->get($url);
        $this->driver->findElement(WebDriverBy::name('email'))->sendKeys($this->login);
        $this->driver->findElement(WebDriverBy::name('pass'))->sendKeys($this->password);
        $this->driver->findElement(WebDriverBy::id('install_allow'))->click();
        sleep(3);
        while ($this->driver->findElements(WebDriverBy::xpath('//input[@name=\'captcha_key\']'))) {
            $this->captcha();
            $this->driver->findElement(WebDriverBy::name('pass'))->sendKeys($this->password);
            $this->driver->findElement(WebDriverBy::id('install_allow'))->click();
            sleep(3);
        }
        $this->driver->wait(60, 1000)->until(
            WebDriverExpectedCondition::titleContains('VK | Request Access')
        );
        $this->driver->findElement(WebDriverBy::id('install_allow'))->click();
        $this->driver->wait(60, 1000)->until(
            WebDriverExpectedCondition::titleContains('OAuth Blank')
        );
        $urlCurrent = $this->driver->getCurrentURL();
        $parseUrl = parse_url($urlCurrent);
        if (!isset($parseUrl['fragment']) && $recursion == true) {
            return $this->getToken($url, false);
        }
        $query = $parseUrl['fragment'];
        parse_str($query, $data);
        return $data;
    }

    private function captcha()
    {
        $path = \Yii::getAlias('@runtime/tmp') . '/' . time() . '_' . rand() . '.png';
        $this->driver->takeScreenshot($path);
        $coordinates = $this->driver->findElement(WebDriverBy::tagName('img'))
            ->getCoordinates()
            ->onPage();
        if (Image::crop($path, $coordinates->getX(), $coordinates->getY(), 130, 50)) {
            if (\Yii::$app->get('captcha')->run($path)) {
                $this->driver->findElement(WebDriverBy::xpath('//input[@name=\'captcha_key\']'))->click();
                $this->driver->findElement(WebDriverBy::xpath('//input[@name=\'captcha_key\']'))->sendKeys(\Yii::$app->get('captcha')->result());
            }
        } else {
            throw new Exception('Проблема при обрезании картинки');
        }
    }
}