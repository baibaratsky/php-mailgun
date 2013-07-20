<?php

class MailgunBounce implements MailgunObject
{
    private $_address;
    private $_code;
    private $_error;
    private $_createDt;

    /**
     * @param array $data
     * @return MailgunBounce
     */
    public static function load($data)
    {
        $bounce = new self($data['address'], $data['code'], $data['error']);
        $bounce->_createDt = new DateTime($data['created_at']);
        return $bounce;
    }

    /**
     * @param string $address   User’s email address
     * @param int $code         Error code (default 550)
     * @param string $error     Error description (default is empty)
     */
    public function __construct($address, $code = 550, $error = null)
    {
        $this->_address = $address;
        $this->_code = $code;
        $this->_error = $error;
    }

    /**
     * @return array POST-data for Mailgun API request
     */
    public function getPostData()
    {
        return array(
            'address' => $this->_address,
            'code' => $this->_code,
            'error' => $this->_error,
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
    public function getCode()
    {
        return $this->_code;
    }

    /**
     * @return string
     */
    public function getError()
    {
        return $this->_error;
    }

    /**
     * @return DateTime
     */
    public function getCreateDt()
    {
        return $this->_createDt;
    }
}
