<?php
/** @author Andrei Baibaratsky */

class MailgunRoute implements MailgunObject
{
    private $_id;
    private $_priority = 0;
    private $_expression = 'catch_all()';
    private $_actions = array();
    private $_description;
    private $_createDt;

    /**
     * @param array $data
     * @return MailgunRoute
     */
    public static function load($data)
    {
        $route = new self;
        $route->_id = $data['id'];
        $route->_priority = $data['priority'];
        $route->_expression = $data['expression'];
        $route->_actions = $data['actions'];
        $route->_description = $data['description'];
        $route->_createDt = new DateTime($data['created_at']);
        return $route;
    }

    /**
     * @return array POST-data for Mailgun API request
     */
    public function getPostData()
    {
        $data = array(
            'priority' => $this->_priority,
            'expression' => $this->_expression,
        );
        foreach ($this->_actions as $number => $action) {
            $data['action[' . ($number + 1) . ']'] = $action;
        }
        if (!empty($this->_description)) {
            $data['description'] = $this->_description;
        }

        return $data;
    }

    /**
     * @param int $priority
     */
    public function setPriority($priority)
    {
        $this->_priority = $priority;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->_priority;
    }

    /**
     * @param string $expression    Route filter {@link http://documentation.mailgun.com/user_manual.html#route-filters}
     */
    public function setExpression($expression)
    {
        $this->_expression = $expression;
    }

    /**
     * Set route filter to match the recipient against the pattern
     * @param string $pattern   Regular expression
     *
     * Mailgun supports regexp captures in filters. This allows you to use captured values inside of your actions.
     * Example:
     * <code>
     *     $route->matchRecipient('(.*)@bar.com');
     *     $route->addForwardAction('http://myhost.com/post/?mailbox=\1');
     * </code>
     * or
     * <code>
     *     $route->matchRecipient('(?P<user>.*?)@(?P<domain>.*?)');
     *     $route->addForwardAction('http://mycallback.com/domains/\g<domain>/users/\g<user>');
     * </code>
     */
    public function matchRecipient($pattern)
    {
        $this->_expression = 'match_recipient("' . $pattern . '")';
    }

    /**
     * Set route filter to match a message header against the pattern
     * @param string $header    MIME header of the message
     * @param string $pattern   Regular expression
     *
     * Mailgun supports regexp captures in filters. This allows you to use captured values inside of your actions.
     * Example:
     * <code>
     *     $route->matchHeader('subject', '(.*)');
     *     $route->addForwardAction('http://myhost.com/post/?subject=\1');
     * </code>
     * or
     * <code>
     *     $route->matchHeader('subject', '\[(?P<id>.*?)\] (?P<name>.*?)');
     *     $route->addForwardAction('http://myhost.com/post/?id=\g<id>&name\g<name>');
     * </code>
     */
    public function matchHeader($header, $pattern)
    {
        $this->_expression = 'match_header("' . $header . '", "' . $pattern . '")';
    }

    /**
     * Set route filter to catch all messages
     */
    public function catchAll()
    {
        $this->_expression = 'catch_all()';
    }

    /**
     * @return string
     */
    public function getExpression()
    {
        return $this->_expression;
    }

    /**
     * @param string $action    Route action {@link http://documentation.mailgun.com/user_manual.html#route-actions}
     */
    public function addAction($action)
    {
        $this->_actions[] = $action;
    }

    /**
     * Append forward action
     * @param string $destination   E-mail address or URL
     */
    public function addForwardAction($destination)
    {
        $this->addAction('forward("' . $destination . '")');
    }

    /**
     * Append stop action
     */
    public function addStopAction()
    {
        $this->addAction('stop()');
    }

    /**
     * @param string $action
     */
    public function removeAction($action)
    {
        foreach ($this->_actions as $key => $value) {
            if ($value == $action) {
                unset($this->_actions[$key]);
            }
        }
    }

    /**
     * Remove forward action
     * @param string $destination   E-mail address or URL
     */
    public function removeForwardAction($destination)
    {
        $this->removeAction('forward("' . $destination . '")');
    }

    /**
     * Remove stop action
     */
    public function removeStopAction()
    {
        $this->removeAction('stop()');
    }

    /**
     * @param array $actions
     */
    public function setActions($actions)
    {
        $this->_actions = $actions;
    }

    /**
     * @return array
     */
    public function getActions()
    {
        return $this->_actions;
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
     * @return DateTime
     */
    public function getCreateDt()
    {
        return $this->_createDt;
    }
}
