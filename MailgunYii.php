<?php
/** @author Andrei Baibaratsky */

require_once 'MailgunApi.php';

class MailgunYii extends CApplicationComponent
{
    public $domain;
    public $key;

    public $viewPath = 'application.views.mail';

    public $fromAddress;
    public $fromName;
    public $tags = array();
    public $campaignId;
    public $enableDkim;
    public $enableTestMode;
    public $enableTracking;
    public $clicksTrackingMode;
    public $enableOpensTracking;

    protected $_api;

    public function __call($name, $parameters)
    {
        if (method_exists($this->_api, $name)) {
            return call_user_func_array(array($this->_api, $name), $parameters);
        }
        return parent::__call($name, $parameters);
    }

    public function init()
    {
        $this->_api = new MailgunApi($this->domain, $this->key);

        if (!empty($this->fromAddress)) {
            $this->_api->setFrom($this->fromAddress, $this->fromName);
        }
        foreach ($this->tags as $tag) {
            $this->_api->addTag($tag);
        }
        if (!empty($this->campaignId)) {
            $this->_api->setCampaignId($this->campaignId);
        }
        if (isset($this->enableDkim)) {
            $this->enableDkim ? $this->_api->enableDkim() : $this->_api->disableDkim();
        }
        if (isset($this->enableTestMode)) {
            $this->enableTestMode ? $this->_api->enableTestMode() : $this->_api->disableTestMode();
        }
        if (isset($this->enableTracking)) {
            $this->enableTracking ? $this->_api->enableTracking() : $this->_api->disableTracking();
        }
        if (isset($this->clicksTrackingMode)) {
            $this->_api->setClicksTrackingMode($this->clicksTrackingMode);
        }
        if (isset($this->enableOpensTracking)) {
            $this->enableOpensTracking ? $this->_api->enableOpensTracking() : $this->_api->disableOpensTracking();
        }
    }

    /**
     * @return MailgunMessageYii
     */
    public function createMessage()
    {
        $message = new MailgunMessageYii($this->_api);
        $message->setViewPath($this->viewPath);
        return $message;
    }
}


class MailgunMessageYii extends MailgunMessage
{
    private $_viewPath;

    /**
     * @param string $viewPath
     */
    public function setViewPath($viewPath)
    {
        $this->_viewPath = $viewPath;
    }

    /**
     * @return string
     */
    public function getViewPath()
    {
        return $this->_viewPath;
    }

    /**
     * Set the message text from view
     * @param $view
     * @param array $params
     */
    public function renderText($view, $params = array())
    {
        $this->setText($this->_render($view, $params));
    }

    /**
     * Set the message HTML from view
     * @param $view
     * @param array $params
     */
    public function renderHtml($view, $params = array())
    {
        $this->setHtml($this->_render($view, $params));
    }

    /**
     * Render message view
     * @param $view
     * @param array $params
     * @return string
     */
    protected function _render($view, $params = array())
    {
        if (isset(Yii::app()->controller)) {
            $controller = Yii::app()->controller;
        } else {
            $controller = new CController(get_class());
        }
        $viewPath = Yii::app()->findLocalizedFile(Yii::getPathOfAlias($this->_viewPath . '.' . $view) . '.php');
        return $controller->renderInternal($viewPath, $params, true);
    }
}
