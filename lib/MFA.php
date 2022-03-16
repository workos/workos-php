<?php

namespace WorkOS;

/**
 * Class MFA.
 *
 * This class facilitates the use of WorkOS MFA.
 */
class MFA
{
    /**
     * Enrolls a new Authentication Factor
     *
     * @param string $type - Type of factor to be enrolled (sms or totp)
     * @param string $totpIssuer - Name of the Organization
     * @param null|string $totpUser - Email of user
     * @param null|string $phoneNumber - Phone number of user
     */

    public function enrollFactor(
        $type = null,
        $totpIssuer = null,
        $totpUser = null,
        $phoneNumber = null
    ) {
        $enrollPath = "auth/factors/enroll";

        if (!isset($type)) {
            $msg = "Incomplete arguments: Need to specify a type of factor";
            throw new Exception\UnexpectedValueException($msg);
        }

        if ($type != "sms" && $type != "totp") {
            $msg = "Type Parameter must either be 'sms' or 'totp'";
            throw new Exception\UnexpectedValueException($msg);
        }

        $params = [
        "type" => $type,
        "totp_issuer" => $totpIssuer,
        "totp_user" => $totpUser,
        "phone_number" => $phoneNumber
    ];
        $response = Client::request(
            Client::METHOD_POST,
            $enrollPath,
            null,
            $params,
            true
        );

        if ($type == "totp") {
            return Resource\AuthenticationFactorTotp::constructFromResponse($response);
        } elseif ($type == "sms") {
            return Resource\AuthenticationFactorSms::constructFromResponse($response);
        }
    }


    /**
     * Initiates the authentication process (a challenge) for an authentication factor
     *
     * @param string $authenticationFactorId - ID of the authentication factor
     * @param string $smsTemplate - Optional parameter to customize the message for sms type factors. Must include "{{code}}" if used.
    */

    public function challengeFactor(
        $authenticationFactorId,
        $smsTemplate
    ) {
        $challengePath = "auth/factors/challenge";

        if (!isset($authenticationFactorId)) {
            $msg = "Incomplete arguments: 'authentication_factor_id' is a required parameter";
            throw new Exception\UnexpectedValueException($msg);
        }

        $params = [
        "authentication_factor_id" => $authenticationFactorId,
        "sms_template" => $smsTemplate
    ];

        $response = Client::request(
            Client::METHOD_POST,
            $challengePath,
            null,
            $params,
            true
        );

        return Resource\AuthenticationChallenge::constructFromResponse($response);
    }

    /**
     * Verifies the one time password provided by the end-user.
     *
     * @param string $authenticationChallengeId - The ID of the authentication challenge that provided the user the verification code.
     * @param string $code - The verification code sent to and provided by the end user.
    */

    public function verifyFactor(
        $authenticationChallengeId,
        $code
    ) {
        $verifyPath = "auth/factors/verify";

        if (!isset($authenticationChallengeId) || !isset($code)) {
            $msg = "Incomplete arguments: 'authenticationChallengeId' and 'code' are required parameters";
            throw new Exception\UnexpectedValueException($msg);
        }

        $params = [
        "authentication_challenge_id" => $authenticationChallengeId,
        "code" => $code
    ];

        $response = Client::request(
            Client::METHOD_POST,
            $verifyPath,
            null,
            $params,
            true
        );

        return Resource\VerificationChallenge::constructFromResponse($response);
    }
}
