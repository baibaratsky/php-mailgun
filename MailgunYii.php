<?php
/** @author Andrei Baibaratsky */

require_once 'MailgunApi.php';

class MailgunYii extends CApplicationComponent
{
    public $domain;
    public $key;

    public $tags = array();
    public $campaignId;
    public $enableDkim;
    public $enableTestMode;
    public $enableTracking;
    public $clicksTrackingMode;
    public $enableOpensTracking;

    private $_api;

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
}
