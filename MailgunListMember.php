<?php
/** @author Andrei Baibaratsky */

class MailgunListMember implements MailgunObject
{
    private $_address;
    private $_name;
    private $_vars = array();
    private $_isSubscribed;

    /**
     * @param array $data
     * @return MailgunListMember
     */
    public static function load($data)
    {
        $member = new self($data['address'], $data['name']);
        $member->setIsSubscribed($data['subscribed']);

        foreach ($data['vars'] as $name => $value) {
            $member->addVar($name, $value);
        }

        return $member;
    }

    /**
     * @param string $address
     * @param string $name
     */
    public function __construct($address, $name = null)
    {
        $this->_address = $address;
        $this->_name = $name;
    }

    /**
     * @return array POST-data for Mailgun API request
     */
    public function getPostData()
    {
        $data = array(
            'address' => $this->_address,
        );

        if (!empty($this->_name)) {
            $data['name'] = $this->_name;
        }
        if (!empty($this->_vars)) {
            $data['vars'] = json_encode($this->_vars);
        }
        if (isset($this->_isSubscribed)) {
            $data['subscribed'] = $this->_isSubscribed ? 'yes' : 'no';
        }

        return $data;
    }

    /**
     * @param string $address
     */
    public function setAddress($address)
    {
        $this->_address = $address;
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->_address;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->_name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Append a custom variable
     * @param string $name  Variable name
     * @param string $value Variable value
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
     * @param mixed $isSubscribed
     */
    public function setIsSubscribed($isSubscribed)
    {
        $this->_isSubscribed = $isSubscribed;
    }

    /**
     * @return mixed
     */
    public function getIsSubscribed()
    {
        return $this->_isSubscribed;
    }
}
