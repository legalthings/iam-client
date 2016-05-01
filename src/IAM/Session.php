<?php

/**
 * Session class
 */
class Session
{
    /**
     * @var User
     */
    public $user;

    /**
     * The datetime that the session was made
     * @var DateTime
     */
    public $date;

    /**
     * The current session
     * @var Session
     */
    public static $session;


    /**
     * Request session through IAM
     * If session exists but was not bound to a user, create anonymous user
     *
     * @param string $id  Session id
     */
    public static function request($id)
    {
        $response = IAM::getSession($id);

        if (isset($response)) {
            $session = new Session();
            $session->date = new DateTime();
            $session->_id = $id;

            if (isset($response['user'])) {
                $organization = new Organization();
                $organization->setValues($response['user']['organization']);
                unset($response['user']['organization']);

                $session->user = new User();
                $session->user->setValues($response['user']);
                $session->user->organization = $organization;
            } else {
                $session->user = new User();
                $session->user->_id = null;
                $session->user->organization = new Organization();
                $session->user->organization->_id = null;
            }

            self::$session = $session;
            return $session;
        }
    }

    /**
     * Create a mock session based on a JSON string
     *
     * @param string $sessionString
     */
    public static function createMock($sessionString)
    {
        $values = json_decode($sessionString, true);

        $session = new Session();
        $session->_id = $values['_id'];
        $session->date = new DateTime($values['date']['date']);

        $organization = new Organization();
        $organization->setValues($values['user']['organization']);
        unset($values['user']['organization']);

        $session->user = new User();
        $session->user->setValues($values['user']);
        $session->user->organization = $organization;

        self::$session = $session;
        return $session;
    }

    /**
     * Get the current session
     */
    public static function getCurrent()
    {
        if (isset(self::$session)) {
            return self::$session;
        } else {
            trigger_error('There is no current session', E_USER_WARNING);
        }
    }
}
