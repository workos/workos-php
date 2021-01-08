<?php

namespace WorkOS;

/**
 * Class Passwordless.
 *
 * This class facilitates the use of WorkOS Magic Link.
 */
class Passwordless
{
    /**
     * Generates a passwordless session.
     *
     * @param string $email Email address of the user that the session is to be created for
     * @param null|string URI to direct the user to user to upon authenticating through the passwordless link
     * @param null|array $state Associative array containing state that will be returned from WorkOS as a json encoded string
     * @param \WorkOS\Resource\ConnectionType $type The only supported ConnectionType at the time of this writing is MagicLink
     *
     * @return  \WorkOS\Resource\PasswordlessSession
     */
    public function createSession($email, $redirectUri, $state, $type)
    {
        $createSessionPath = "passwordless/sessions";
    
        $params = [
            "email" => $email,
            "type" => $type
        ];

        if ($redirectUri) {
            $params["redirect_uri"] = $redirectUri;
        }

        if (null !== $state && !empty($state)) {
            $params["state"] = \json_encode($state);
        }

        $response = Client::request(Client::METHOD_POST, $createSessionPath, null, $params, true);

        return Resource\PasswordlessSession::constructFromResponse($response);
    }


    /** Send a passwordless link via email from WorkOS.
     *
     * @param \WorkOS\Resource\PasswordlessSession $session Passwordless session generated through Passwordless->createSession
     *
     * @return boolean true
     */
    public function sendSession($session)
    {
        $sendSessionPath = "passwordless/sessions/$session->id/send";
        Client::request(Client::METHOD_POST, $sendSessionPath, null, null, true);

        return true;
    }
}
