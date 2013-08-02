<?php
/** @author Andrei Baibaratsky */

require_once 'MailgunApi.php';

/**
 * Class MailgunYii
 *
 * @method string               sendMessage(MailgunMessageYii $message)
 * @method MailgunList[]        getMailingLists(int $limit = 100, int $skip = 0)
 * @method MailgunList          getMailingList(string $listAddress)
 * @method MailgunList          createMailingList(MailgunList $mailingList)
 * @method MailgunList          updateMailingList(string $listAddress, MailgunList $mailingList)
 * @method bool                 deleteMailingList(string $listAddress)
 * @method MailgunListMember[]  getMailingListMembers(string $listAddress, int $limit = 100, int $skip = 0)
 * @method MailgunListMember    getMailingListMember(string $listAddress, string $memberAddress)
 * @method MailgunListMember    addMemberToMailingList(string $listAddress, MailgunListMember $member, bool $upsert = false)
 * @method MailgunList          addMultipleMembersToMailingList(string $listAddress, array $members)
 * @method MailgunListMember    updateMailingListMember(string $listAddress, string $memberAddress, MailgunListMember $member)
 * @method bool                 deleteMailingListMember(string $listAddress, string $memberAddress)
 * @method array                getMailingListStats(string $listAddress)
 * @method MailgunUnsubscribe[] getUnsubscribes(int $limit = 100, int $skip = 0)
 * @method MailgunUnsubscribe[] getUserUnsubscribes(string $userAddress)
 * @method bool                 createUnsubscribe(MailgunUnsubscribe $unsubscribe)
 * @method bool                 deleteUnsubscribe(string $id)
 * @method bool                 deleteUserUnsubscribes(string $userAddress)
 * @method MailgunComplaint[]   getComplaints(int $limit = 100, int $skip = 0)
 * @method MailgunComplaint     getComplaint(string $userAddress)
 * @method bool                 createComplaint(MailgunComplaint $complaint)
 * @method bool                 deleteComplaint(string $userAddress)
 * @method MailgunBounce[]      getBounces(int $limit = 100, int $skip = 0)
 * @method MailgunBounce        getBounce(string $userAddress)
 * @method bool                 createBounce(MailgunBounce $bounce)
 * @method bool                 deleteBounce(string $userAddress)
 * @method MailgunRoute[]       getRoutes(int $limit = 100, int $skip = 0)
 * @method MailgunRoute         getRoute(string $id)
 * @method MailgunRoute         createRoute(MailgunRoute $route)
 * @method MailgunRoute         updateRoute(string $id, MailgunRoute $route)
 * @method bool                 deleteRoute(string $id)
 * @method bool                 validateHook(array $data)
 * @method void                 setFrom(string $address, string $name = null)
 * @method string               getFrom()
 * @method void                 addTag(string $tag)
 * @method string[]             getTags()
 * @method void                 setCampaignId(string $campaignId)
 * @method string               getCampaignId()
 * @method void                 enableDkim()
 * @method void                 disableDkim()
 * @method bool                 getIsDkimEnabled()
 * @method void                 enableTestMode()
 * @method void                 disableTestMode()
 * @method bool                 getIsTestModeEnabled()
 * @method void                 enableTracking()
 * @method void                 disableTracking()
 * @method bool                 getIsTrackingEnabled()
 * @method void                 enableClicksTracking(bool $htmlOnly = false)
 * @method void                 disableClicksTracking()
 * @method void                 setClicksTrackingMode(int $clicksTrackingMode)
 * @method int                  getClicksTrackingMode()
 * @method void                 enableOpensTracking()
 * @method void                 disableOpensTracking()
 * @method bool                 getIsOpensTrackingEnabled()
 */
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
    public function newMessage()
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
