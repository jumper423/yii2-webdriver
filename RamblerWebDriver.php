<?php

namespace jumper423;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use yii\base\Exception;

class RamblerWebDriver extends WebDriverBase
{
    public $email;
    public $password;

    /**
     * Включаем доступ сторонним программам
     * @throws Exception
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     * @throws null
     */
    public function accessThirdPartyPrograms()
    {
        $this->driver->get('https://mail.rambler.ru/');
        if (strpos($this->driver->getTitle(), 'Входящие') === false ) {
            $this->driver->findElement(WebDriverBy::name('login'))->sendKeys($this->email);
            $this->driver->findElement(WebDriverBy::id('form_password'))->sendKeys($this->password);
            $this->driver->findElement(WebDriverBy::cssSelector('button.form-button.form-button_submit'))->click();

            $this->driver->wait(60, 1000)->until(
                WebDriverExpectedCondition::titleContains('Входящие')
            );
        }

        $this->driver->get('https://mail.rambler.ru/#/settings/clients/');
        sleep(1);
        if(!$this->driver->findElement(WebDriverBy::cssSelector('fieldset.settingsFieldset.settingsFieldsetExternal > label > input.uiCheckbox'))->isSelected()){
            do {
                $this->driver->findElement(WebDriverBy::cssSelector('fieldset.settingsFieldset.settingsFieldsetExternal > label > input.uiCheckbox'))->click();
                sleep(1);
                $this->captcha();
                $this->driver->navigate()->refresh();
                sleep(1);
            } while (!$this->driver->findElement(WebDriverBy::cssSelector('fieldset.settingsFieldset.settingsFieldsetExternal > label > input.uiCheckbox'))->isSelected());
        }
    }

    private function captcha(){
        $path = \Yii::getAlias('@runtime/tmp') . '/' . time() . '_' . rand() . '.png';
        $this->driver->takeScreenshot($path);
        $coordinates = $this->driver->findElement(WebDriverBy::cssSelector('.captchaImage'))
            ->getCoordinates()
            ->onPage();
        if (Image::crop($path, $coordinates->getX(), $coordinates->getY(), 300, 60)) {
            if (\Yii::$app->get('captcha')->run($path)) {
                $this->driver->findElement(WebDriverBy::id('CaptchaInput'))->click();
                $this->driver->findElement(WebDriverBy::id('CaptchaInput'))->sendKeys(\Yii::$app->get('captcha')->result());
                $this->driver->findElement(WebDriverBy::name('submit_captcha'))->click();
                sleep(2);
            }
        } else {
            throw new Exception('Проблема при обрезании картинки');
        }
    }
}