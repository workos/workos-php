<?php

namespace WorkOS\Live;

class AuthTest extends \PHPUnit\Framework\TestCase
{
    use \WorkOS\TestHelper {
        setUp as traitSetUp;
    }
    use Common;

    protected function setUp(): void
    {

        \WorkOS\WorkOS::setApiKey(self::API_KEY);
        \WorkOS\WorkOS::setClientId(self::CLIENT_ID);
        \WorkOS\WorkOS::setApiBaseUrl("https://api.workos-test.com/");
        $this->userManagement = new \WorkOS\UserManagement();
    }

    public function testAll()
    {
        print "Create a user\n";
        $rnd = self::rndStr();
        $emailAuth = "jb+{$rnd}@uselessresistance.com";
        $pwdAuth = "x^T!V23UN1@V";
        $names = $this->randomName();
        $fn = $names[0];
        $ln = $names[1];
        $emailVerified = false;
        $user = $this->userManagement->createUser($emailAuth, $pwdAuth, $fn, $ln, $emailVerified);
        var_dump($user);

        $userID = $user->id;

        // Enroll Auth factor
        print "Enroll Auth Factor \n";
        $response = $this->userManagement->enrollAuthFactor($user->id, "totp");
        var_dump($response);


        // List Auth Factors (not checked)
        print "List Auth Factors\n";
        $response = $this->userManagement->listAuthFactors($user->id);
        var_dump($response);

        // Send email verification
        print "Send Verification Email \n";
        $response = $this->userManagement->sendVerificationEmail($user->id);
        var_dump($response);
        print "Email for verification {$response->user->email} \n";
        print "Id for verification {$response->user->id} \n";

        // Verify Email
        // Code coming from a real email
        try {
            print "Verify Email with code\n";
            $response = $this->userManagement->verifyEmail("user_01HH2PYT3XB4K6JG937CZV1P2J", "437755");
            var_dump($response);
        } catch (\WorkOS\Exception\BaseRequestException $ex) {
            print "error Verify Email !\n";
        }

        // Send magic auth code
        print "Send magic auth code\n";
        $rnd = self::rndStr();
        $emailAuth = "jb+{$rnd}@uselessresistance.com";
        $response = $this->userManagement->sendMagicAuthCode($emailAuth);
        var_dump($response);

        print "Send password reset email \n";
        $response = $this->userManagement->sendPasswordResetEmail($user->email, "https://your-app.com/reset-password");
        var_dump($response);
        $token = $response->token;

        print "Authenticate with pwd with no organization\n";
        $rnd = self::rndStr();
        $emailAuth = "jb+{$rnd}@uselessresistance.com";
        $pwdAuth = "x^T!V23UN1@V";
        $names = $this->randomName();
        $fn = $names[0];
        $ln = $names[1];
        $emailVerified = true;
        $user = $this->userManagement->createUser($emailAuth, $pwdAuth, $fn, $ln, $emailVerified);
        var_dump($user);
        try {
            $response = $this->userManagement->authenticateWithPassword(self::CLIENT_ID, $user->email, $pwdAuth, "127.0.0.1", "Super-cool client PHP");
            var_dump($response);
        } catch (\WorkOS\Exception\AuthorizationException $ex) {
            print "fail ! maybe because 2fa is enabled \n";
        }

        print "Authenticate with pwd multiple organizations\n";
        $rnd = self::rndStr();
        $emailAuth = "jb+{$rnd}@uselessresistance.com";
        $names = $this->randomName();
        $fn = $names[0];
        $ln = $names[1];
        $emailVerified = true;
        $user = $this->userManagement->createUser($emailAuth, $pwdAuth, $fn, $ln, $emailVerified);
        var_dump($user);
        $response = $this->userManagement->createOrganizationMembership($user->id, "org_01HFQWS6TVSPKQJ3TGSPBMCQ0H");
        var_dump($response);
        $response = $this->userManagement->createOrganizationMembership($user->id, "org_01HFSP0Z534H951J97KF1NR6WM");
        var_dump($response);

        try {
            $response = $this->userManagement->authenticateWithPassword(self::CLIENT_ID, $user->email, $pwdAuth, "127.0.0.1", "Super-cool client PHP");
        } catch (\WorkOS\Exception\AuthorizationException $ex) {
            var_dump($ex);
            if ($ex->responseCode != "mfa_enrollment") {
                $json = $ex->response->json();
                $pendingAuthenticationToken = $json["pending_authentication_token"];
                $orgId = $json["organizations"][0]["organization_id"];
                print "token {$pendingAuthenticationToken} \n";
                print "orgId {$orgId} \n";

                $response = $this->userManagement->authenticateWithSelectedOrganization(
                    self::CLIENT_ID,
                    $pendingAuthenticationToken,
                    $orgId,
                    "127.0.0.2",
                    "Super agent org select"
                );
                var_dump($response);
            }
        }

        // Magic Auth
        print "Authenticate with pwd No organizations at all\n";
        print "Create a fresh user \n";
        $rnd = self::rndStr();
        $emailAuth = "jb+{$rnd}@uselessresistance.com";
        $names = $this->randomName();
        $fn = $names[0];
        $ln = $names[1];
        $emailVerified = true;
        $user = $this->userManagement->createUser($emailAuth, $pwdAuth, $fn, $ln, $emailVerified);
        var_dump($user);
        print "Send magic Auth code \n";
        $response = $this->userManagement->sendMagicAuthCode($user->email);
        var_dump($response);

        // code is coming from an email of a previous run
        try {
            print "Auth with magic code \n";
            $response = $this->userManagement->authenticateWithMagicAuth(
                self::CLIENT_ID,
                "552762",
                "user_01HH0XENNQZ27GZA5QT1V29V4V",
                $ipAddress = "127.0.0.3",
                $userAgent = "Magic auth !!!",
            );
            var_dump($response);
        } catch (\WorkOS\Exception\BadRequestException $ex) {
            print "Expected error \n";
        }

        // Auth with totp
        //$this->authWithTotp();

        // Auth with code
        $this->authWithCode();
    }

    private function authWithCode()
    {
        $pwdAuth = "x^T!V23UN1@V";
        // Auth with totp
        print "Authenticate with TOTP \n";
        // create a user email verified
        $rnd = self::rndStr();
        $emailAuth = "jb+{$rnd}@uselessresistance.com";
        $names = $this->randomName();
        $fn = $names[0];
        $ln = $names[1];
        $emailVerified = true;
        $user = $this->userManagement->createUser($emailAuth, $pwdAuth, $fn, $ln, $emailVerified);
        var_dump($user);

        $code = readline("Open AuthKit, https://unassuming-mist-22-staging.authkit-test.app/ and auth with email={$user->email} and pwd={$pwdAuth}, get the code in the call back url : ");

        $response = $this->userManagement->authenticateWithCode(
            self::CLIENT_ID,
            $code,
            "127.0.0.1",
            "Super-cool client PHP",
        );
        var_dump($response);
    }

    private function authWithTotp()
    {
        $pwdAuth = "x^T!V23UN1@V";
        // Auth with totp
        print "Authenticate with TOTP \n";
        // create a user email verified
        $rnd = self::rndStr();
        $emailAuth = "jb+{$rnd}@uselessresistance.com";
        $names = $this->randomName();
        $fn = $names[0];
        $ln = $names[1];
        $emailVerified = true;
        $user = $this->userManagement->createUser($emailAuth, $pwdAuth, $fn, $ln, $emailVerified);
        var_dump($user);

        // make sure mfa is required in dashboard
        // manual operation only

        // Enroll Auth Factor
        print "Enroll Auth Factor \n";
        $response = $this->userManagement->enrollAuthFactor($user->id, "totp");
        var_dump($response);

        // extract challenge id
        $authenticationChallengeId = $response->authenticationChallenge->id;
        print "authenticationChallengeId= {$authenticationChallengeId} \n";

        // extract otp key
        $optKey = $response->authenticationFactor->totp["secret"];
        print "optKey= {$authenticationChallengeId} \n";

        // try to authenticate with password
        print "Authenticate with pwd \n";
        try {
            $response = $this->userManagement->authenticateWithPassword(self::CLIENT_ID, $user->email, $pwdAuth, "127.0.0.1", "Super-cool client PHP");
            var_dump($response);
        } catch (\WorkOS\Exception\AuthorizationException $ex) {
            // extract the pending auth token

            $json = $ex->response->json();
            $pendingAuthenticationToken = $json["pending_authentication_token"];

            // Setup otp with the secret in the reponse in 1password
            $code = readline("Code from opt app key={$optKey}: ");

            print "Here's a code {$code} \n";

            $response = $this->userManagement->authenticateWithTotp(
                self::CLIENT_ID,
                $pendingAuthenticationToken,
                $authenticationChallengeId,
                $code,
                "127.0.0.1",
                "Super-cool client PHP"
            );
            var_dump($response);
        }
    }
}
