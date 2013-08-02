Mailgun API PHP library
=======================

The library requires PHP 5.2 compiled with [cURL extension](http://www.php.net/manual/en/book.curl.php).

It’s pretty easy to send a message using this library:
```php
$mailgun = new MailgunApi('example.com', 'key-somekey');

$message = $mailgun->newMessage();
$message->setFrom('me@example.com', 'Andrei Baibaratsky');
$message->addTo('you@yourdomain.com', 'My dear user');
$message->setSubject('Mailgun API library test');
$message->setText('Amazing! It’s working!');
$message->addTag('test'); // All the Mailgun-specific attributes, such as tags, vars, tracking, etc. are supported

$message->enableTestMode(); // Don’t forget to remove this string if you really want the message to be sent

echo $message->send();
```

Batch sending is also supported. You can use [recipient variables](http://documentation.mailgun.com/user_manual.html#batch-sending) to customize messages.

The library fully supports listing, creating, updating, and deleting of mailing lists and their members:
```
MailgunList[]        getMailingLists(int $limit = 100, int $skip = 0)
MailgunList          getMailingList(string $listAddress)
MailgunList          createMailingList(MailgunList $mailingList)
MailgunList          updateMailingList(string $listAddress, MailgunList $mailingList)
bool                 deleteMailingList(string $listAddress)
MailgunListMember[]  getMailingListMembers(string $listAddress, int $limit = 100, int $skip = 0)
MailgunListMember    getMailingListMember(string $listAddress, string $memberAddress)
MailgunListMember    addMemberToMailingList(string $listAddress, MailgunListMember $member, bool $upsert = false)
MailgunList          addMultipleMembersToMailingList(string $listAddress, array $members)
MailgunListMember    updateMailingListMember(string $listAddress, string $memberAddress, MailgunListMember $member)
bool                 deleteMailingListMember(string $listAddress, string $memberAddress)
array                getMailingListStats(string $listAddress)
```

Unsubscribes-related methods:
```
MailgunUnsubscribe[] getUnsubscribes(int $limit = 100, int $skip = 0)
MailgunUnsubscribe[] getUserUnsubscribes(string $userAddress)
bool                 createUnsubscribe(MailgunUnsubscribe $unsubscribe)
bool                 deleteUnsubscribe(string $id)
bool                 deleteUserUnsubscribes(string $userAddress)
```

Spam Complaints:
```
MailgunComplaint[]   getComplaints(int $limit = 100, int $skip = 0)
MailgunComplaint     getComplaint(string $userAddress)
bool                 createComplaint(MailgunComplaint $complaint)
bool                 deleteComplaint(string $userAddress)
```

Bounces:
```
MailgunBounce[]      getBounces(int $limit = 100, int $skip = 0)
MailgunBounce        getBounce(string $userAddress)
bool                 createBounce(MailgunBounce $bounce)
bool                 deleteBounce(string $userAddress)
```

Routes:
```
MailgunRoute[]       getRoutes(int $limit = 100, int $skip = 0)
MailgunRoute         getRoute(string $id)
MailgunRoute         createRoute(MailgunRoute $route)
MailgunRoute         updateRoute(string $id, MailgunRoute $route)
bool                 deleteRoute(string $id)
```

Webhook signature validation:
```php
if (!$mailgun->validateHook($_POST)) {
    echo 'Bad signature!';
}
```

###Yii extension
Yii users can use this library as an extension. Just put *php-mailgun* in your extensions directory and add some code in the *components* section of your config file:
```php
...
    'components' => array(
        ...
        'mailgun' => array(
            'class' => 'application.extensions.php-mailgun.MailgunYii',
            'domain' => 'example.com',
            'key' => 'key-somekey',
            'tags' => array('yii'), // You may also specify some Mailgun parameters
            'enableTracking' => false,
        ),
        ...
    ),
...
```
That’s all! Your application is ready to send messages. For example:
```php
$message = Yii::app()->mailgun->newMessage();
$message->setFrom('me@example.com', 'Andrei Baibaratsky');
$message->addTo('you@yourdomain.com', 'My dear user');
$message->setSubject('Mailgun API library test');

// You can use views to build your messages instead of setText() or setHtml():
$message->renderText('myView', array('myParam' => 'Awesome!'));

echo $message->send();
```
All the methods of the main library class are available in the Yii component.


---
Mailgun is a programmable email platform. It allows your application to become a fully-featured email server. Send and receive messages, create mailboxes and email campaigns with ease.
You can find more information about Mailgun and its API here: http://documentation.mailgun.com
