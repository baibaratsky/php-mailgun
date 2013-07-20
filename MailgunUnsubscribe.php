<?php

class MailgunUnsubscribe implements MailgunObject
{
    private $_id;
    private $_address;
    private $_tag;
    private $_createDt;

    /**
     * @param array $data
     * @return MailgunUnsubscribe
     */
    public static function load($data)
    {
        $unsubscribe = new self($data['address'], $data['tag']);
        $unsubscribe->_id = $data['id'];
        $unsubscribe->_createDt = new DateTime($data['created_at']);
        return $unsubscribe;
    }

    /**
     * @param string $address   User’s email address
     * @param string $tag       Tag to unsubscribe from, use '*' to unsubscribe address from domain (default)
     */
    public function __construct($address, $tag = '*')
    {
        $this->_address = $address;
        $this->_tag = $tag;
    }

    /**
     * @return array POST-data for Mailgun API request
     */
    public function getPostData()
    {
        return array(
            'address' => $this->_address,
            'tag' => $this->_tag,
        );
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->_address;
    }

    /**
     * @return string
     */
    public function getTag()
    {
        return $this->_tag;
    }

    /**
     * @return DateTime
     */
    public function getCreateDt()
    {
        return $this->_createDt;
    }
}
