<?php

class MailgunComplaint implements MailgunObject
{
    private $_address;
    private $_count;
    private $_createDt;

    /**
     * @param array $data
     * @return MailgunComplaint
     */
    public static function load($data)
    {
        $complaint = new self($data['address']);
        $complaint->_count = $data['count'];
        $complaint->_createDt = new DateTime($data['created_at']);
        return $complaint;
    }

    /**
     * @param string $address   User’s email address
     */
    public function __construct($address)
    {
        $this->_address = $address;
    }

    /**
     * @return array POST-data for Mailgun API request
     */
    public function getPostData()
    {
        return array(
            'address' => $this->_address,
        );
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->_address;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        return $this->_count;
    }

    /**
     * @return DateTime
     */
    public function getCreateDt()
    {
        return $this->_createDt;
    }
}
