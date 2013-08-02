<?php
/** @author Andrei Baibaratsky */

require_once 'MailgunBounce.php';
require_once 'MailgunComplaint.php';
require_once 'MailgunList.php';
require_once 'MailgunListMember.php';
require_once 'MailgunMessage.php';
require_once 'MailgunRoute.php';
require_once 'MailgunUnsubscribe.php';

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
     * @return MailgunMessage
     */
    public function newMessage()
    {
        return new MailgunMessage($this);
    }

    /**
     * @param MailgunMessage $message
     * @return string Mailgun ID of the message
     */
    public function sendMessage(MailgunMessage $message)
    {
        $response = $this->_performRequest('POST', $this->_url . $this->_domain . '/messages', $message);
        return $response['id'];
    }

    /**
     * @param int $limit    Maximum number of records to return (100 by default)
     * @param int $skip     Records to skip (0 by default)
     * @return MailgunList[]
     */
    public function getMailingLists($limit = 100, $skip = 0)
    {
        $response = $this->_performRequest('GET', $this->_url . 'lists?limit=' . $limit . '&skip=' . $skip);
        $mailingLists = array();
        foreach ($response['items'] as $item) {
            $mailingLists[$item['address']] = MailgunList::load($item);
        }
        return $mailingLists;
    }

    /**
     * @param string $listAddress   Address of the mailing list to find
     * @return MailgunList
     */
    public function getMailingList($listAddress)
    {
        $response = $this->_performRequest('GET', $this->_url . 'lists/' . $listAddress);
        return MailgunList::load($response['list']);
    }

    /**
     * @param MailgunList $mailingList  Mailing list object to create
     * @return MailgunList              Created mailing list object
     */
    public function createMailingList(MailgunList $mailingList)
    {
        $response = $this->_performRequest('POST', $this->_url . 'lists', $mailingList);
        return MailgunList::load($response['list']);
    }

    /**
     * @param string $listAddress       Address of the mailing list to update
     * @param MailgunList $mailingList  Mailing list object containing new data
     * @return MailgunList              Updated mailing list object
     */
    public function updateMailingList($listAddress, MailgunList $mailingList)
    {
        $response = $this->_performRequest('PUT', $this->_url . 'lists/' . $listAddress, $mailingList);
        return MailgunList::load($response['list']);
    }

    /**
     * @param string $listAddress   Address of the mailing list to delete
     * @return bool                 Whether the list has been successfully deleted
     */
    public function deleteMailingList($listAddress)
    {
        $response = $this->_performRequest('DELETE', $this->_url . 'lists/' . $listAddress);
        return $response['message'] == 'Mailing list has been deleted';
    }

    /**
     * @param string $listAddress   Address of the mailing list
     * @param int $limit            Maximum number of records to return (100 by default)
     * @param int $skip             Records to skip (0 by default)
     * @return MailgunListMember[]
     */
    public function getMailingListMembers($listAddress, $limit = 100, $skip = 0)
    {
        $response = $this->_performRequest('GET', $this->_url . 'lists/' . $listAddress
                                                              . '/members?limit=' . $limit . '&skip=' . $skip);
        $members = array();
        foreach ($response['items'] as $item) {
            $members[$item['address']] = MailgunListMember::load($item);
        }
        return $members;
    }

    /**
     * @param string $listAddress   Address of the mailing list
     * @param string $memberAddress Address of the mailing list member to find
     * @return MailgunListMember
     */
    public function getMailingListMember($listAddress, $memberAddress)
    {
        $response = $this->_performRequest('GET', $this->_url . 'lists/' . $listAddress . '/members/' . $memberAddress);
        return MailgunListMember::load($response['member']);
    }

    /**
     * @param string $listAddress           Address of the mailing list
     * @param MailgunListMember $member     Member to add to the mailing list
     * @param bool $upsert                  Whether to update member if present (defaults to false)
     * @return MailgunListMember
     */
    public function addMemberToMailingList($listAddress, MailgunListMember $member, $upsert = false)
    {
        $response = $this->_performRequest('POST', $this->_url . 'lists/' . $listAddress . '/members', $member,
                                           array('upsert' => $upsert ? 'yes' : 'no'));
        return MailgunListMember::load($response['member']);
    }

    /**
     * Add multiple mailing list members (limit 1,000 per call)
     *
     * @param string $listAddress                   Address of the mailing list
     * @param array[]|MailgunListMember[] $members  Members to add to the mailing list
     * @return MailgunList                          Updated mailing list
     * @throws MailgunException
     */
    public function addMultipleMembersToMailingList($listAddress, $members)
    {
        if (count($members) == 0) {
            throw new MailgunException('The members list is empty.');
        }

        if (count($members) > 1000) {
            throw new MailgunException('The maximum number of members allowed in one request is 1,000.');
        }

        if (is_array($members[0])) {
            $data['members'] = $members;
        } else {
            $data['members'] = array();
            foreach ($members as $member) {
                $data['members'][] = $member->getPostData();
            }
        }
        $data['members'] = json_encode($data['members']);

        $response = $this->_performRequest('POST', $this->_url . 'lists/' . $listAddress . '/members.json', null, $data);

        return MailgunList::load($response['list']);
    }

    /**
     * @param string $listAddress       Address of the mailing list
     * @param string $memberAddress     Address of the mailing list member to update
     * @param MailgunListMember $member Mailing list member object containing new data
     * @return MailgunListMember        Updated list member object
     */
    public function updateMailingListMember($listAddress, $memberAddress, MailgunListMember $member)
    {
        $response = $this->_performRequest('PUT', $this->_url . 'lists/' . $listAddress . '/members/' . $memberAddress,
                                           $member);
        return MailgunListMember::load($response['member']);
    }

    /**
     * @param string $listAddress   Address of the mailing list
     * @param string $memberAddress Address of the mailing list member to delete
     * @return bool                 Whether the member has been successfully deleted
     */
    public function deleteMailingListMember($listAddress, $memberAddress)
    {
        $response = $this->_performRequest('DELETE', $this->_url . 'lists/' . $listAddress
                                                                 . '/members/' . $memberAddress);
        return $response['message'] == 'Mailing list member has been deleted';
    }

    /**
     * @param string $listAddress
     * @return array
     */
    public function getMailingListStats($listAddress)
    {
        return $this->_performRequest('GET', $this->_url . 'lists/' . $listAddress . '/stats');
    }

    /**
     * @param int $limit            Maximum number of records to return (100 by default)
     * @param int $skip             Records to skip (0 by default)
     * @return MailgunUnsubscribe[]
     */
    public function getUnsubscribes($limit = 100, $skip = 0)
    {
        $response = $this->_performRequest('GET', $this->_url . $this->_domain
                                                              . '/unsubscribes?limit=' . $limit . '&skip=' . $skip);
        $unsubscribes = array();
        foreach ($response['items'] as $item) {
            $unsubscribes[$item['id']] = MailgunUnsubscribe::load($item);
        }
        return $unsubscribes;
    }

    /**
     * @param string $userAddress   Address of the user to find
     * @return MailgunUnsubscribe[]
     */
    public function getUserUnsubscribes($userAddress)
    {
        $response = $this->_performRequest('GET', $this->_url . $this->_domain . '/unsubscribes/' . $userAddress);

        $unsubscribes = array();
        foreach ($response['items'] as $item) {
            $unsubscribes[$item['id']] = MailgunUnsubscribe::load($item);
        }
        return $unsubscribes;
    }

    /**
     * @param MailgunUnsubscribe $unsubscribe   Unsubscribe object to create
     * @return bool                             Whether the unsubscribe record has been successfully added
     */
    public function createUnsubscribe(MailgunUnsubscribe $unsubscribe)
    {
        $response = $this->_performRequest('POST', $this->_url . $this->_domain . '/unsubscribes', $unsubscribe);
        return $response['message'] == 'Address has been added to the unsubscribes table';
    }

    /**
     * @param string $id    Id of the unsubscribe record to delete
     * @return bool         Whether the record has been successfully deleted
     */
    public function deleteUnsubscribe($id)
    {
        $response = $this->_performRequest('DELETE', $this->_url . $this->_domain . '/unsubscribes/' . $id);
        return $response['message'] == 'Unsubscribe event has been removed';
    }

    /**
     * @param string $userAddress   User’s address to delete unsubscribe records
     * @return bool                 Whether the records have been successfully deleted
     */
    public function deleteUserUnsubscribes($userAddress)
    {
        $response = $this->_performRequest('DELETE', $this->_url . $this->_domain . '/unsubscribes/' . $userAddress);
        return $response['message'] == 'Unsubscribe event has been removed';
    }

    /**
     * @param int $limit            Maximum number of records to return (100 by default)
     * @param int $skip             Records to skip (0 by default)
     * @return MailgunComplaint[]
     */
    public function getComplaints($limit = 100, $skip = 0)
    {
        $response = $this->_performRequest('GET', $this->_url . $this->_domain
                                                              . '/complaints?limit=' . $limit . '&skip=' . $skip);
        $unsubscribes = array();
        foreach ($response['items'] as $item) {
            $unsubscribes[$item['address']] = MailgunComplaint::load($item);
        }
        return $unsubscribes;
    }

    /**
     * @param string $userAddress   Address of the user to find
     * @return MailgunComplaint[]
     */
    public function getComplaint($userAddress)
    {
        $response = $this->_performRequest('GET', $this->_url . $this->_domain . '/complaints/' . $userAddress);
        return MailgunComplaint::load($response['complaint']);
    }

    /**
     * @param MailgunComplaint $complaint   Complaint object to create
     * @return bool                         Whether the complaint record has been successfully added
     */
    public function createComplaint(MailgunComplaint $complaint)
    {
        $response = $this->_performRequest('POST', $this->_url . $this->_domain . '/complaints', $complaint);
        return $response['message'] == 'Address has been added to the complaints table';
    }

    /**
     * @param string $userAddress   User’s address to delete complaint record
     * @return bool                 Whether the complaint record has been successfully deleted
     */
    public function deleteComplaint($userAddress)
    {
        $response = $this->_performRequest('DELETE', $this->_url . $this->_domain . '/complaints/' . $userAddress);
        return $response['message'] == 'Spam complaint has been removed';
    }

    /**
     * @param int $limit            Maximum number of records to return (100 by default)
     * @param int $skip             Records to skip (0 by default)
     * @return MailgunBounce[]
     */
    public function getBounces($limit = 100, $skip = 0)
    {
        $response = $this->_performRequest('GET', $this->_url . $this->_domain
                                                              . '/bounces?limit=' . $limit . '&skip=' . $skip);
        $unsubscribes = array();
        foreach ($response['items'] as $item) {
            $unsubscribes[$item['address']] = MailgunBounce::load($item);
        }
        return $unsubscribes;
    }

    /**
     * @param string $userAddress   Address of the user to find
     * @return MailgunBounce[]
     */
    public function getBounce($userAddress)
    {
        $response = $this->_performRequest('GET', $this->_url . $this->_domain . '/bounces/' . $userAddress);
        return MailgunBounce::load($response['bounce']);
    }

    /**
     * @param MailgunBounce $bounce     Bounce object to create
     * @return bool                     Whether the bounce record has been successfully added
     */
    public function createBounce(MailgunBounce $bounce)
    {
        $response = $this->_performRequest('POST', $this->_url . $this->_domain . '/bounces', $bounce);
        return $response['message'] == 'Address has been added to the bounces table';
    }

    /**
     * @param string $userAddress   User’s address to delete bounce record
     * @return bool                 Whether the bounce record has been successfully deleted
     */
    public function deleteBounce($userAddress)
    {
        $response = $this->_performRequest('DELETE', $this->_url . $this->_domain . '/bounces/' . $userAddress);
        return $response['message'] == 'Bounced address has been removed';
    }

    /**
     * @param int $limit    Maximum number of records to return (100 by default)
     * @param int $skip     Records to skip (0 by default)
     * @return MailgunRoute[]
     */
    public function getRoutes($limit = 100, $skip = 0)
    {
        $response = $this->_performRequest('GET', $this->_url . 'routes?limit=' . $limit . '&skip=' . $skip);
        $routes = array();
        foreach ($response['items'] as $item) {
            $routes[$item['id']] = MailgunRoute::load($item);
        }
        return $routes;
    }

    /**
     * @param string $id    Id of the route to find
     * @return MailgunRoute
     */
    public function getRoute($id)
    {
        $response = $this->_performRequest('GET', $this->_url . 'routes/' . $id);
        return MailgunRoute::load($response['route']);
    }

    /**
     * @param MailgunRoute $route   Route object to create
     * @return MailgunRoute         Created route object
     */
    public function createRoute(MailgunRoute $route)
    {
        $response = $this->_performRequest('POST', $this->_url . 'routes', $route);
        return MailgunRoute::load($response['route']);
    }

    /**
     * @param string $id            Address of the route to update
     * @param MailgunRoute $route   Route object containing new data
     * @return MailgunRoute         Updated route object
     */
    public function updateRoute($id, MailgunRoute $route)
    {
        $response = $this->_performRequest('PUT', $this->_url . 'routes/' . $id, $route);
        return MailgunRoute::load($response);
    }

    /**
     * @param string $id   Address of the mailing list to delete
     * @return bool                 Whether the list has been successfully deleted
     */
    public function deleteRoute($id)
    {
        $response = $this->_performRequest('DELETE', $this->_url . 'routes/' . $id);
        return $response['message'] == 'Route has been deleted';
    }

    /**
     * @param array $data   POST data of the request
     * @return bool
     */
    public function validateHook($data)
    {
        return hash_hmac('sha256', $data['timestamp'] . $data['token'], $this->_key) === $data['signature'];
    }

    /**
     * @param string $address   Default email address for From header of new messages
     * @param string $name      Default sender name
     */
    public function setFrom($address, $name = null)
    {
        $this->_from = array($address, $name);
    }

    /**
     * @return array Default email address for From header of new messages
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
     * @return resource cURL instance
     */
    protected function _getCurl()
    {
        if (!$this->_curl) {
            $this->_curl = curl_init();
            curl_setopt($this->_curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($this->_curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($this->_curl, CURLOPT_TIMEOUT, $this->_timeout);
            curl_setopt($this->_curl, CURLOPT_USERPWD, 'api:' . $this->_key);
        }
        return $this->_curl;
    }

    /**
     * @param string $method
     * @param string $url
     * @param MailgunObject $object
     * @param array $params
     * @return array
     * @throws MailgunException
     */
    protected function _performRequest($method, $url, MailgunObject $object = null, $params = array())
    {
        $curl = $this->_getCurl();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);

        if ($object !== null) {
            $params = array_merge($object->getPostData(), $params);
        }

        if (!empty($params)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        }

        $response = curl_exec($curl);

        if ($response === false) {
            throw new MailgunException(curl_error($curl));
        }

        $responseStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($responseStatus >= 500) {
            throw new MailgunException('Mailgun server error', $responseStatus);
        } else {
            $responseArray = json_decode($response, true);
            if ($responseStatus == 200) {
                return $responseArray;
            } else {
                throw new MailgunException(!empty($responseArray['message']) ? $responseArray['message'] : $response,
                                           $responseStatus);
            }
        }
    }
}


class MailgunException extends Exception
{
}


interface MailgunObject
{
    /**
     * @return array POST-data for Mailgun API request
     */
    public function getPostData();
}
