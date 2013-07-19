Mailgun API PHP library
=======================

The library requires PHP 5 compiled with [cURL extension](http://www.php.net/manual/en/book.curl.php).

Only sending messages using API is supported at the moment. It works pretty easy:
```php
$mailgun = new MailgunApi('example.com', 'key-somekey');

$message = $mailgun->newMessage();
$message->setFrom('me@example.com', 'Andrei Baibaratsky');
$message->addTo('you@yourdomain.com', 'My dear user');
$message->setSubject('Mailgun API library test');
$message->setText('Amazing! It’s working!');
$message->addTag('test'); // All the Mailgun-specific attributes, such as tags, vars, tracking, etc. are supported

$message->enableTestMode(); // Don’t forget to remove this string if you really want the message to be sent

echo $mailgun->sendMessage($message);
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

echo Yii::app()->mailgun->sendMessage($message);
```
---
Mailgun is a programmable email platform. It allows your application to become a fully-featured email server. Send and receive messages, create mailboxes and email campaigns with ease.
You can find more information about Mailgun and its API here: http://documentation.mailgun.com
