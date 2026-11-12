<?php

declare(strict_types=1);

// @oagen-ignore-file

namespace WorkOS\Exception;

/**
 * Thrown when workload identity credentials cannot produce an access token:
 * a missing setting, a failing identity token provider, or a token endpoint
 * that rejected or could not complete the exchange.
 *
 * The message is `<errorCode>: <hint>`, followed by the description when
 * there is one. It never contains an identity token or an access token.
 */
class WorkloadIdentityException extends \Exception implements WorkOSException
{
    /** The token endpoint could not be reached, or kept failing. */
    public const EXCHANGE_FAILED = 'exchange_failed';

    /** The token endpoint answered with something that is not a token response. */
    public const INVALID_RESPONSE = 'invalid_response';

    /** The identity token provider failed. */
    public const PROVIDER_FAILED = 'provider_failed';

    /** A required setting is missing. */
    public const INVALID_CONFIGURATION = 'invalid_configuration';

    private const DESCRIPTION_LIMIT = 500;

    private const DEFAULT_HINT = 'The token endpoint rejected the exchange.';

    /**
     * Every hint, keyed by error code. The wording is pending the server
     * team's final error codes; change it here and nowhere else.
     */
    private const HINTS = [
        'invalid_grant' => 'The identity token did not match a trust rule for this application, or the token has expired. Check the issuer, audience and subject claims against the trust rule.',
        'invalid_target' => 'The resource or audience is wrong. The trust rule\'s audience must match the audience the identity token was requested with, and the resource must be registered for the environment.',
        'invalid_client' => 'Check that the client ID is the M2M application that has the trust rule.',
        'unauthorized_client' => 'This application is not allowed to use workload identity federation. Check that the feature is enabled for the environment and that the application has a trust rule.',
        'invalid_scope' => 'The requested scope is not allowed for this application. Fix the scope or leave it unset.',
        'invalid_request' => 'The token request was malformed. Check that the token URL points at the `/oauth2/token` endpoint.',
        self::EXCHANGE_FAILED => 'The token endpoint could not be reached or kept failing. Check the token URL and try again.',
        self::INVALID_RESPONSE => 'The token endpoint returned a response the SDK could not read. Check that the token URL points at the `/oauth2/token` endpoint.',
        self::PROVIDER_FAILED => 'The identity token provider failed, so there was no token to exchange.',
        self::INVALID_CONFIGURATION => 'Workload identity credentials are missing a required setting.',
    ];

    /**
     * The OAuth error code from the token endpoint, or one of this class's
     * constants. Named `errorCode`, like {@see ApiException::$errorCode},
     * because `Exception::$code` is an integer.
     */
    public readonly string $errorCode;

    /** The token endpoint's `error_description`, cut to 500 characters; empty when there is none. */
    public readonly string $description;

    /** One sentence on what to check. Never empty. */
    public readonly string $hint;

    public function __construct(string $errorCode, string $description = '', ?\Throwable $previous = null)
    {
        $this->errorCode = $errorCode;
        $this->description = mb_substr($description, 0, self::DESCRIPTION_LIMIT);
        $this->hint = self::HINTS[$errorCode] ?? self::DEFAULT_HINT;

        $message = sprintf('%s: %s', $this->errorCode, $this->hint);
        if ($this->description !== '') {
            $message .= ' ' . $this->description;
        }

        parent::__construct($message, 0, $previous);
    }
}
