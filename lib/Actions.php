<?php

namespace WorkOS;

use WorkOS\Resource\WebhookResponse;
use WorkOS\Resource\Webhook as WebhookResource;

/**
 * Class Actions.
 *
 * This class provides convenient methods for implementing WorkOS Actions,
 * allowing you to control user registration and authentication flows.
 *
 * @see https://workos.com/docs/authkit/actions
 */
class Actions
{
    /**
     * Allow a user registration request.
     *
     * Example:
     * ```php
     * $actions = new \WorkOS\Actions();
     * $response = $actions->allowUserRegistration('webhook_secret_123');
     * echo json_encode($response->toArray());
     * ```
     *
     * @param string $webhookSecret Webhook secret from WorkOS dashboard
     * @param string|null $reason Optional reason for allowing (for logging purposes)
     * @return WebhookResponse
     */
    public function allowUserRegistration($webhookSecret, $reason = null)
    {
        return WebhookResponse::create(
            WebhookResponse::USER_REGISTRATION_ACTION,
            $webhookSecret,
            WebhookResponse::VERDICT_ALLOW,
            $reason
        );
    }

    /**
     * Deny a user registration request.
     *
     * Example:
     * ```php
     * $actions = new \WorkOS\Actions();
     * $response = $actions->denyUserRegistration('webhook_secret_123', 'Domain not allowed');
     * echo json_encode($response->toArray());
     * ```
     *
     * @param string $webhookSecret Webhook secret from WorkOS dashboard
     * @param string $reason Required reason for denying the registration
     * @return WebhookResponse
     */
    public function denyUserRegistration($webhookSecret, $reason)
    {
        return WebhookResponse::create(
            WebhookResponse::USER_REGISTRATION_ACTION,
            $webhookSecret,
            WebhookResponse::VERDICT_DENY,
            $reason
        );
    }

    /**
     * Allow an authentication request.
     *
     * Example:
     * ```php
     * $actions = new \WorkOS\Actions();
     * $response = $actions->allowAuthentication('webhook_secret_123');
     * echo json_encode($response->toArray());
     * ```
     *
     * @param string $webhookSecret Webhook secret from WorkOS dashboard
     * @param string|null $reason Optional reason for allowing (for logging purposes)
     * @return WebhookResponse
     */
    public function allowAuthentication($webhookSecret, $reason = null)
    {
        return WebhookResponse::create(
            WebhookResponse::AUTHENTICATION_ACTION,
            $webhookSecret,
            WebhookResponse::VERDICT_ALLOW,
            $reason
        );
    }

    /**
     * Deny an authentication request.
     *
     * Example:
     * ```php
     * $actions = new \WorkOS\Actions();
     * $response = $actions->denyAuthentication('webhook_secret_123', 'User account is suspended');
     * echo json_encode($response->toArray());
     * ```
     *
     * @param string $webhookSecret Webhook secret from WorkOS dashboard
     * @param string $reason Required reason for denying the authentication
     * @return WebhookResponse
     */
    public function denyAuthentication($webhookSecret, $reason)
    {
        return WebhookResponse::create(
            WebhookResponse::AUTHENTICATION_ACTION,
            $webhookSecret,
            WebhookResponse::VERDICT_DENY,
            $reason
        );
    }

    /**
     * Verify a webhook signature to ensure it came from WorkOS.
     *
     * Example:
     * ```php
     * $actions = new \WorkOS\Actions();
     * if (!$actions->verifyWebhook($signatureHeader, $payload, $secret)) {
     *     http_response_code(401);
     *     exit('Invalid signature');
     * }
     * ```
     *
     * @param string $signatureHeader The WorkOS signature header
     * @param string $payload The request payload
     * @param string $secret The webhook secret
     * @param int $tolerance Time tolerance in seconds (default: 300)
     * @return bool True if the signature is valid
     */
    public function verifyWebhook($signatureHeader, $payload, $secret, $tolerance = 300)
    {
        $webhook = new Webhook();
        return $webhook->verifyHeader($signatureHeader, $payload, $secret, $tolerance) === 'pass';
    }

    /**
     * Parse a webhook payload into a structured object.
     *
     * Example:
     * ```php
     * $actions = new \WorkOS\Actions();
     * $webhook = $actions->parseWebhook($jsonPayload);
     *
     * if ($actions->isUserRegistrationWebhook($webhook)) {
     *     $email = $actions->extractEmail($webhook);
     * }
     * ```
     *
     * @param string $payload The JSON payload
     * @return object The parsed webhook object
     */
    public function parseWebhook($payload)
    {
        return WebhookResource::constructFromPayload($payload);
    }

    /**
     * Check if a webhook is a user registration action.
     *
     * Example:
     * ```php
     * $actions = new \WorkOS\Actions();
     * $webhook = $actions->parseWebhook($payload);
     *
     * if ($actions->isUserRegistrationWebhook($webhook)) {
     *     // Handle user registration logic
     * }
     * ```
     *
     * @param object $webhook The parsed webhook object
     * @return bool True if this is a user registration webhook
     */
    public function isUserRegistrationWebhook($webhook)
    {
        return isset($webhook->object) && $webhook->object === 'user_registration_action_context';
    }

    /**
     * Check if a webhook is an authentication action.
     *
     * Example:
     * ```php
     * $actions = new \WorkOS\Actions();
     * $webhook = $actions->parseWebhook($payload);
     *
     * if ($actions->isAuthenticationWebhook($webhook)) {
     *     // Handle authentication logic
     * }
     * ```
     *
     * @param object $webhook The parsed webhook object
     * @return bool True if this is an authentication webhook
     */
    public function isAuthenticationWebhook($webhook)
    {
        return isset($webhook->object) && $webhook->object === 'authentication_action_context';
    }

    /**
     * Safely extract email from either webhook type.
     *
     * Example:
     * ```php
     * $actions = new \WorkOS\Actions();
     * $webhook = $actions->parseWebhook($payload);
     * $email = $actions->extractEmail($webhook);
     *
     * if ($email) {
     *     // Process email
     * }
     * ```
     *
     * @param object $webhook The parsed webhook object
     * @return string|null The email address or null if not found
     */
    public function extractEmail($webhook)
    {
        // For authentication webhooks
        if (isset($webhook->user) && isset($webhook->user->email)) {
            return $webhook->user->email;
        }

        // For registration webhooks
        if (isset($webhook->user_data) && isset($webhook->user_data->email)) {
            return $webhook->user_data->email;
        }

        return null;
    }

    /**
     * Safely extract user ID from authentication webhooks.
     *
     * Example:
     * ```php
     * $actions = new \WorkOS\Actions();
     * $webhook = $actions->parseWebhook($payload);
     * $userId = $actions->extractUserId($webhook);
     *
     * if ($userId) {
     *     // Process user ID
     * }
     * ```
     *
     * @param object $webhook The parsed webhook object
     * @return string|null The user ID or null if not found
     */
    public function extractUserId($webhook)
    {
        if (isset($webhook->user) && isset($webhook->user->id)) {
            return $webhook->user->id;
        }

        return null;
    }

    /**
     * Safely extract organization ID from either webhook type.
     *
     * Example:
     * ```php
     * $actions = new \WorkOS\Actions();
     * $webhook = $actions->parseWebhook($payload);
     * $orgId = $actions->extractOrganizationId($webhook);
     *
     * if ($orgId) {
     *     // Process organization ID
     * }
     * ```
     *
     * @param object $webhook The parsed webhook object
     * @return string|null The organization ID or null if not found
     */
    public function extractOrganizationId($webhook)
    {
        // For authentication webhooks
        if (isset($webhook->organization) && isset($webhook->organization->id)) {
            return $webhook->organization->id;
        }

        // For registration webhooks
        if (isset($webhook->invitation) && isset($webhook->invitation->organization_id)) {
            return $webhook->invitation->organization_id;
        }

        return null;
    }
}
