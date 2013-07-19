<?php
/** @author Andrei Baibaratsky */

require_once 'MailgunMessage.php';

class MailgunApi
{
    protected $_curl;
    protected $_url = 'https://api.mailgun.net/v2/';
    protected $_timeout = 10;

    private $_from;
    private $_tags = array();
    private $_campaignId;
    private $_enableDkim;
    private $_enableTestMode;
    private $_enableTracking;
    private $_clicksTrackingMode;
    private $_enableOpensTracking;

    private $_domain;
    private $_key;

    /**
     * @param string $domain
     * @param string $key
     */
    public function __construct($domain, $key)
    {
        $this->_domain = $domain;
        $this->_key = $key;
    }

    public function __destruct()
    {
        if (isset($this->_curl)) {
            curl_close($this->_curl);
        }
    }

    /**
     * Create new message
     * @return MailgunMessage
     */
    public function createMessage()
    {
        return new MailgunMessage($this);
    }

    /**
     * @param MailgunMessage $message
     * @return string Mailgun ID of the message
     * @throws MailgunException
     */
    public function sendMessage(MailgunMessage $message)
    {
        $curl = $this->_getCurl();
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, 'api:' . $this->_key);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_URL, $this->_url . $this->_domain . '/messages');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $message->getPostData());

        $response = curl_exec($curl);

        if ($response === false) {
            throw new MailgunException(curl_error($curl));
        }

        $responseStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($responseStatus >= 500) {
            throw new MailgunException('Mailgun server error', $responseStatus);
        } else {
            $responseArray = $this->_jsonDecode($response);
            if ($responseStatus == 200) {
                return $responseArray['id'];
            } else {
                throw new MailgunException(!empty($responseArray['message']) ? $responseArray['message'] : $response,
                                           $responseStatus);
            }
        }
    }

    /**
     * @param string $address   Email address for From header
     * @param string $name      Sender name
     */
    public function setFrom($address, $name = null)
    {
        $this->_from = array($address, $name);
    }


    /**
     * @return array Email address for From header
     */
    public function getFrom()
    {
        return $this->_from;
    }

    /**
     * @param string $tag
     */
    public function addTag($tag)
    {
        $this->_tags[] = $tag;
    }

    /**
     * @return string[]
     */
    public function getTags()
    {
        return $this->_tags;
    }

    /**
     * @param string $campaignId Id of the campaign the message belongs to
     */
    public function setCampaignId($campaignId)
    {
        $this->_campaignId = $campaignId;
    }

    /**
     * @return string Id of the campaign the message belongs to
     */
    public function getCampaignId()
    {
        return $this->_campaignId;
    }

    public function enableDkim()
    {
        $this->_enableDkim = true;
    }

    public function disableDkim()
    {
        $this->_enableDkim = false;
    }

    /**
     * @return bool
     */
    public function getIsDkimEnabled()
    {
        return $this->_enableDkim;
    }

    public function enableTestMode()
    {
        $this->_enableTestMode = true;
    }

    public function disableTestMode()
    {
        $this->_enableTestMode = false;
    }

    /**
     * @return bool
     */
    public function getIsTestModeEnabled()
    {
        return $this->_enableTestMode;
    }

    public function enableTracking()
    {
        $this->_enableTracking = true;
    }

    public function disableTracking()
    {
        $this->_enableTracking = false;
    }

    /**
     * @return bool
     */
    public function getIsTrackingEnabled()
    {
        return $this->_enableTracking;
    }

    /**
     * @param bool $htmlOnly Enable clicks tracking just for HTML-body
     */
    public function enableClicksTracking($htmlOnly = false)
    {
        $this->_clicksTrackingMode = $htmlOnly ?
                MailgunMessage::CLICKS_TRACKING_HTML_ONLY : MailgunMessage::CLICKS_TRACKING_ENABLED;
    }

    public function disableClicksTracking()
    {
        $this->_clicksTrackingMode = MailgunMessage::CLICKS_TRACKING_DISABLED;
    }

    /**
     * @param int $clicksTrackingMode
     */
    public function setClicksTrackingMode($clicksTrackingMode)
    {
        $this->_clicksTrackingMode = $clicksTrackingMode;
    }

    /**
     * @return int See MailgunMessage::CLICKS_TRACKING constants for possible values
     */
    public function getClicksTrackingMode()
    {
        return $this->_clicksTrackingMode;
    }

    public function enableOpensTracking()
    {
        $this->_enableOpensTracking = true;
    }

    public function disableOpensTracking()
    {
        $this->_enableOpensTracking = false;
    }

    /**
     * @return bool
     */
    public function getIsOpensTrackingEnabled()
    {
        return $this->_enableOpensTracking;
    }

    /**
     * @return resource CURL instance
     */
    protected function _getCurl()
    {
        if (!$this->_curl) {
            $this->_curl = curl_init();
            curl_setopt($this->_curl, CURLOPT_TIMEOUT, $this->_timeout);
            curl_setopt($this->_curl, CURLOPT_RETURNTRANSFER, true);
        }
        return $this->_curl;
    }

    /**
     * @param string $json
     * @return array
     */
    protected function _jsonDecode($json)
    {
        return json_decode($json, true);
    }
}

class MailgunException extends Exception
{
}
