<?php
/** @author Andrei Baibaratsky */

class MailgunList implements MailgunObject
{
    const ACCESS_LEVEL_READONLY = 'readonly';
    const ACCESS_LEVEL_MEMBERS = 'members';
    const ACCESS_LEVEL_EVERYONE = 'everyone';

    private $_address;
    private $_name;
    private $_description;
    private $_accessLevel = self::ACCESS_LEVEL_READONLY;
    private $_membersCount = 0;
    private $_createDt;

    /**
     * @param array $data
     * @return MailgunList
     */
    public static function load($data)
    {
        $mailingList = new self($data['address'], $data['name'], $data['description'], $data['access_level']);
        $mailingList->_membersCount = $data['members_count'];
        $mailingList->_createDt = new DateTime($data['created_at']);
        return $mailingList;
    }

    /**
     * @param string $address
     * @param string $name
     * @param string $description
     * @param string $accessLevel
     */
    public function __construct($address, $name = null, $description = null, $accessLevel = self::ACCESS_LEVEL_READONLY)
    {
        $this->_address = $address;
        $this->_name = $name;
        $this->_description = $description;
        $this->_accessLevel = $accessLevel;
    }

    /**
     * @return array POST-data for mailing list in Mailgun API
     */
    public function getPostData()
    {
        $data = array(
            'address' => $this->_address,
            'access_level' => $this->_accessLevel,
        );

        if (!empty($this->_name)) {
            $data['name'] = $this->_name;
        }
        if (!empty($this->_description)) {
            $data['description'] = $this->_description;
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
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->_description = $description;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->_description;
    }

    /**
     * @param string $accessLevel
     */
    public function setAccessLevel($accessLevel)
    {
        $this->_accessLevel = $accessLevel;
    }

    /**
     * @return string
     */
    public function getAccessLevel()
    {
        return $this->_accessLevel;
    }

    /**
     * @return int
     */
    public function getMembersCount()
    {
        return $this->_membersCount;
    }

    /**
     * @return DateTime
     */
    public function getCreateDt()
    {
        return $this->_createDt;
    }
}
