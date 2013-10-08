<?php
/** @author Andrei Baibaratsky */

class MailgunMessage implements MailgunObject
{
    const CLICKS_TRACKING_DISABLED = 0;
    const CLICKS_TRACKING_ENABLED = 1;
    const CLICKS_TRACKING_HTML_ONLY = 2;

    private $_from;
    private $_to = array();
    private $_cc = array();
    private $_bcc = array();
    private $_replyTo = array();
    private $_subject;
    private $_text;
    private $_html;
    private $_attachments = array();
    private $_inline = array();
    private $_tags = array();
    private $_campaignId;
    private $_enableDkim;
    private $_enableTestMode;
    private $_enableTracking;
    private $_clicksTrackingMode;
    private $_enableOpensTracking;
    private $_headers = array();
    private $_vars = array();
    private $_recipientVars = array();

    protected $_api;

    /** @var DateTime */
    private $_deliveryTime;

    public function __construct(MailgunApi $api)
    {
        $this->_api = $api;
        $this->_from = $api->getFrom();
        $this->_tags = $api->getTags();
        $this->_campaignId = $api->getCampaignId();
        $this->_enableDkim = $api->getIsDkimEnabled();
        $this->_enableTestMode = $api->getIsTestModeEnabled();
        $this->_enableTracking = $api->getIsTrackingEnabled();
        $this->_clicksTrackingMode = $api->getClicksTrackingMode();
        $this->_enableOpensTracking = $api->getIsOpensTrackingEnabled();
    }

    /**
     * @return string Mailgun ID of the message
     */
    public function send()
    {
        return $this->_api->sendMessage($this);
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
     * @param string $address       Email address of the recipient
     * @param string $name          Recipient name
     * @param array  $recipientVars Custom variables that you define, which you can then reference in the message body
     * @throws MailgunException
     *
     * Recipient vars give you the ability to send a custom message to each recipient while still using a single API call
     * @link http://documentation.mailgun.com/user_manual.html#batch-sending
     */
    public function addTo($address, $name = null, $recipientVars = null)
    {
        if (count($this->_to) >= 1000) {
            throw new MailgunException('The maximum number of recipients allowed for Batch Sending is 1,000.');
        }

        $this->_to[] = array($address, $name);
        if (is_array($recipientVars)) {
            $this->_recipientVars[$address] = $recipientVars;
        }
    }

    /**
     * @return array[]
     */
    public function getTo()
    {
        return $this->_to;
    }

    /**
     * @param string $address   Email address for Carbon copy
     * @param string $name      Recipient name
     */
    public function addCc($address, $name = null)
    {
        $this->_cc[] = array($address, $name);
    }

    /**
     * @return array[]
     */
    public function getCc()
    {
        return $this->_cc;
    }

    /**
     * @param string $address   Email address for Blind carbon copy
     * @param string $name      Recipient name
     */
    public function addBcc($address, $name = null)
    {
        $this->_bcc[] = array($address, $name);
    }

    /**
     * @return array[]
     */
    public function getBcc()
    {
        return $this->_bcc;
    }

    /**
     * @param string $address   Email address
     * @param string $name      Recipient name
     */
    public function addReplyTo($address, $name = null)
    {
        $this->_replyTo[] = array($address, $name);
    }

    /**
     * @return array[]
     */
    public function getReplyTo()
    {
        return $this->_replyTo;
    }

    /**
     * @param string $subject
     */
    public function setSubject($subject)
    {
        $this->_subject = $subject;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->_subject;
    }

    /**
     * @param string $text
     */
    public function setText($text)
    {
        $this->_text = $text;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->_text;
    }

    /**
     * @param string $html
     */
    public function setHtml($html)
    {
        $this->_html = $html;
    }

    /**
     * @return string
     */
    public function getHtml()
    {
        return $this->_html;
    }

    /**
     * @param string $fileName Absolute path name of the attachment
     */
    public function addAttachment($fileName)
    {
        $this->_attachments[] = $fileName;
    }

    /**
     * @return array List of the inline attachments
     */
    public function getAttachments()
    {
        return $this->_attachments;
    }

    /**
     * @param string $fileName Absolute path name of the inline attached file
     */
    public function addInline($fileName)
    {
        $this->_inline[] = $fileName;
    }

    /**
     * @return array List of the inline attached files
     */
    public function getInline()
    {
        return $this->_inline;
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

    /**
     * Enables DKIM signatures on per-message basis. Overrides common settings.
     */
    public function enableDkim()
    {
        $this->_enableDkim = true;
    }

    /**
     * Disables DKIM signatures on per-message basis. Overrides common settings.
     */
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

    /**
     * @param DateTime $deliveryTime Desired time of delivery
     */
    public function setDeliveryTime(DateTime $deliveryTime)
    {
        $this->_deliveryTime = $deliveryTime;
    }

    /**
     * @return DateTime Desired time of delivery
     */
    public function getDeliveryTime()
    {
        return $this->_deliveryTime;
    }

    /**
     * Enables test mode on per-message basis. Overrides common settings.
     */
    public function enableTestMode()
    {
        $this->_enableTestMode = true;
    }

    /**
     * Disables test mode on per-message basis. Overrides common settings.
     */
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

    /**
     * Enables tracking on a per-message basis. Overrides common settings.
     */
    public function enableTracking()
    {
        $this->_enableTracking = true;
    }

    /**
     * Disables tracking on a per-message basis. Overrides common settings.
     */
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
     * Enables clicks tracking on a per-message basis. Overrides common settings.
     * @param bool $htmlOnly Enable clicks tracking just for HTML-body
     */
    public function enableClicksTracking($htmlOnly = false)
    {
        $this->_clicksTrackingMode = $htmlOnly ? self::CLICKS_TRACKING_HTML_ONLY : self::CLICKS_TRACKING_ENABLED;
    }

    /**
     * Disables clicks tracking on a per-message basis. Overrides common settings.
     */
    public function disableClicksTracking()
    {
        $this->_clicksTrackingMode = self::CLICKS_TRACKING_DISABLED;
    }

    /**
     * Sets clicks tracking mode on a per-message basis. Overrides common settings.
     * @param int $clicksTrackingMode
     */
    public function setClicksTrackingMode($clicksTrackingMode)
    {
        $this->_clicksTrackingMode = $clicksTrackingMode;
    }

    /**
     * @return int See CLICKS_TRACKING constants for possible values
     */
    public function getClicksTrackingMode()
    {
        return $this->_clicksTrackingMode;
    }

    /**
     * Enables opens tracking on a per-message basis. Overrides common settings.
     */
    public function enableOpensTracking()
    {
        $this->_enableOpensTracking = true;
    }

    /**
     * Disables opens tracking on a per-message basis. Overrides common settings.
     */
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
     * Append a custom MIME header to the message
     * @param string $name  Header name
     * @param string $value Header value
     *
     * @example $message->addHeader('Reply-To', 'some@address.com');
     */
    public function addHeader($name, $value)
    {
        $this->_headers[$name] = $value;
    }

    /**
     * @return array List of additional headers in 'name' => 'value' format
     */
    public function getHeaders()
    {
        return $this->_headers;
    }

    /**
     * Append a custom variable to the message
     * @param string $name  Variable name
     * @param string $value Variable value
     *
     * @example $message->addVar('myMessageId', '888');
     */
    public function addVar($name, $value)
    {
        $this->_vars[$name] = $value;
    }

    /**
     * @return array List of attached variables in 'name' => 'value' format
     */
    public function getVars()
    {
        return $this->_vars;
    }

    /**
     * @return array POST-data for sending message using Mailgun API
     */
    public function getPostData()
    {
        $data = array(
            'from' => $this->_formatAddress($this->_from),
            'to' => $this->_formatAddresses($this->_to),
            'subject' => $this->getSubject(),
        );

        if (!empty($this->_cc)) {
            $data['cc'] = $this->_formatAddresses($this->_cc);
        }
        if (!empty($this->_bcc)) {
            $data['bcc'] = $this->_formatAddresses($this->_bcc);
        }
        if (!empty($this->_replyTo)) {
            $data['h:Reply-To'] = $this->_formatAddresses($this->_replyTo);
        }
        if (!empty($this->_text)) {
            $data['text'] = $this->_text;
        }
        if (!empty($this->_html)) {
            $data['html'] = $this->_html;
        }

        foreach ($this->_attachments as $number => $attachment) {
            $data['attachment[' . ($number + 1) . ']'] = '@' . $attachment;
        }

        foreach ($this->_inline as $number => $attachment) {
            $data['inline[' . ($number + 1) . ']'] = '@' . $attachment;
        }

        foreach ($this->_tags as $number => $tag) {
            $data['o:tag[' . ($number + 1) . ']'] = $tag;
        }

        if (!empty($this->_campaignId)) {
            $data['o:campaign'] = $this->_campaignId;
        }

        if (isset($this->_enableDkim)) {
            $data['o:dkim'] = $this->_enableDkim ? 'yes' : 'no';
        }

        if (isset($this->_deliveryTime)) {
            $data['o:deliverytime'] = $this->_deliveryTime->format('r');
        }

        if (!empty($this->_enableTestMode)) {
            $data['o:testmode'] = 'yes';
        }

        if (isset($this->_enableTracking)) {
            $data['o:tracking'] = $this->_enableTracking ? 'yes' : 'no';
        }

        if (isset($this->_clicksTrackingMode)) {
            switch ($this->_clicksTrackingMode) {
                case self::CLICKS_TRACKING_DISABLED:
                    $data['o:tracking'] = 'no';
                    break;

                case self::CLICKS_TRACKING_ENABLED:
                    $data['o:tracking'] = 'yes';
                    break;

                case self::CLICKS_TRACKING_HTML_ONLY:
                    $data['o:tracking'] = 'htmlonly';
                    break;

                default:
            }
        }

        if (isset($this->_enableOpensTracking)) {
            $data['o:tracking-opens'] = $this->_enableOpensTracking ? 'yes' : 'no';
        }

        foreach ($this->_headers as $name => $value) {
            $data['h:' . $name] = $value;
        }

        foreach ($this->_vars as $name => $value) {
            $data['v:' . $name] = $value;
        }

        if (!empty($this->_recipientVars)) {
            $data['recipient-variables'] = json_encode($this->_recipientVars);
        }

        return $data;
    }

    /**
     * @param array $addressData
     * @return string
     */
    private function _formatAddress(array $addressData)
    {
        list($address, $name) = $addressData;
        if (is_null($name)) {
            return $address;
        }
        return $name . ' <' . $address . '>';
    }

    /**
     * @param array $addresses
     * @return string
     */
    private function _formatAddresses(array $addresses)
    {
        $formattedAddresses = array();
        foreach ($addresses as $address) {
            $formattedAddresses[] = $this->_formatAddress($address);
        }
        return implode(', ', $formattedAddresses);
    }
}
