<?php

namespace WorkOS;

/**
 * Common event types for IDE autocomplete.
 * This list is NOT exhaustive - the API accepts additional values.
 * @see https://workos.com/docs/events
 */
class EventTypes
{
    // Authentication Events
    public const AUTHENTICATION_EMAIL_VERIFICATION_SUCCEEDED = 'authentication.email_verification_succeeded';
    public const AUTHENTICATION_MAGIC_AUTH_FAILED = 'authentication.magic_auth_failed';
    public const AUTHENTICATION_MAGIC_AUTH_SUCCEEDED = 'authentication.magic_auth_succeeded';
    public const AUTHENTICATION_MFA_FAILED = 'authentication.mfa_failed';
    public const AUTHENTICATION_MFA_SUCCEEDED = 'authentication.mfa_succeeded';
    public const AUTHENTICATION_OAUTH_FAILED = 'authentication.oauth_failed';
    public const AUTHENTICATION_OAUTH_SUCCEEDED = 'authentication.oauth_succeeded';
    public const AUTHENTICATION_PASSWORD_FAILED = 'authentication.password_failed';
    public const AUTHENTICATION_PASSWORD_SUCCEEDED = 'authentication.password_succeeded';
    public const AUTHENTICATION_PASSKEY_FAILED = 'authentication.passkey_failed';
    public const AUTHENTICATION_PASSKEY_SUCCEEDED = 'authentication.passkey_succeeded';
    public const AUTHENTICATION_SSO_FAILED = 'authentication.sso_failed';
    public const AUTHENTICATION_SSO_SUCCEEDED = 'authentication.sso_succeeded';
    public const AUTHENTICATION_RADAR_RISK_DETECTED = 'authentication.radar_risk_detected';

    // Connection Events
    public const CONNECTION_ACTIVATED = 'connection.activated';
    public const CONNECTION_DEACTIVATED = 'connection.deactivated';
    public const CONNECTION_DELETED = 'connection.deleted';
    public const CONNECTION_SAML_CERTIFICATE_RENEWED = 'connection.saml_certificate_renewed';
    public const CONNECTION_SAML_CERTIFICATE_RENEWAL_REQUIRED = 'connection.saml_certificate_renewal_required';

    // DSync Events
    public const DSYNC_ACTIVATED = 'dsync.activated';
    public const DSYNC_DELETED = 'dsync.deleted';
    public const DSYNC_GROUP_CREATED = 'dsync.group.created';
    public const DSYNC_GROUP_DELETED = 'dsync.group.deleted';
    public const DSYNC_GROUP_UPDATED = 'dsync.group.updated';
    public const DSYNC_GROUP_USER_ADDED = 'dsync.group.user_added';
    public const DSYNC_GROUP_USER_REMOVED = 'dsync.group.user_removed';
    public const DSYNC_USER_CREATED = 'dsync.user.created';
    public const DSYNC_USER_DELETED = 'dsync.user.deleted';
    public const DSYNC_USER_UPDATED = 'dsync.user.updated';

    // Email Verification Events
    public const EMAIL_VERIFICATION_CREATED = 'email_verification.created';

    // Flag Events
    public const FLAG_CREATED = 'flag.created';
    public const FLAG_UPDATED = 'flag.updated';
    public const FLAG_DELETED = 'flag.deleted';
    public const FLAG_RULE_UPDATED = 'flag.rule_updated';

    // Invitation Events
    public const INVITATION_ACCEPTED = 'invitation.accepted';
    public const INVITATION_CREATED = 'invitation.created';
    public const INVITATION_REVOKED = 'invitation.revoked';

    // Organization Events
    public const ORGANIZATION_CREATED = 'organization.created';
    public const ORGANIZATION_UPDATED = 'organization.updated';
    public const ORGANIZATION_DELETED = 'organization.deleted';
    public const ORGANIZATION_DOMAIN_CREATED = 'organization_domain.created';
    public const ORGANIZATION_DOMAIN_UPDATED = 'organization_domain.updated';
    public const ORGANIZATION_DOMAIN_DELETED = 'organization_domain.deleted';
    public const ORGANIZATION_DOMAIN_VERIFIED = 'organization_domain.verified';
    public const ORGANIZATION_DOMAIN_VERIFICATION_FAILED = 'organization_domain.verification_failed';
    public const ORGANIZATION_MEMBERSHIP_CREATED = 'organization_membership.created';
    public const ORGANIZATION_MEMBERSHIP_DELETED = 'organization_membership.deleted';
    public const ORGANIZATION_MEMBERSHIP_UPDATED = 'organization_membership.updated';

    // Password Reset Events
    public const PASSWORD_RESET_CREATED = 'password_reset.created';
    public const PASSWORD_RESET_SUCCEEDED = 'password_reset.succeeded';

    // Role Events
    public const ROLE_CREATED = 'role.created';
    public const ROLE_DELETED = 'role.deleted';
    public const ROLE_UPDATED = 'role.updated';

    // Session Events
    public const SESSION_CREATED = 'session.created';
    public const SESSION_REVOKED = 'session.revoked';

    // User Events
    public const USER_CREATED = 'user.created';
    public const USER_DELETED = 'user.deleted';
    public const USER_UPDATED = 'user.updated';
}
