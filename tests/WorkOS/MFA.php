<?php

namespace WorkOS;

class MFATest extends \PHPUnit\Framework\TestCase
{
    use TestHelper {
        setUp as traitSetUp;
    }

    protected function setUp(): void
    {
        $this->traitSetUp();

        $this->withApiKeyAndClientId();
        $this->mfa = new MFA();
    }

    public function testEnrollFactorTotp()
    {
        $type = "totp";
        $path = "auth/factors/enroll";
        $params = [
            "client_id" => WorkOS::getClientId(),
            "client_secret" => WorkOS::getApikey(),
            "type" => $type,
            "totp_issuer" => $totpIssuer,
            "totp_user" => $totpUser,
            "phone_number" => $phoneNumber
        ];

        $result = $this->enrollFactorTotpResponseFixture();

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            false,
            $result
        );

        $enrollFactorTotp = $this->mfa->enrollFactor($type, "test", "ricksanchez", "1234567890" );
        $enrollFactorTotpFixture = $this->enrollFactorTotpResponseFixture();

        $this->assertSame($enrollFactorTotpFixture, $enrollFactorTotp);
    }

    public function testEnrollFactorSms()
    {
        $type = "sms";
        $path = "auth/factors/enroll";
        $params = [
            "client_id" => WorkOS::getClientId(),
            "client_secret" => WorkOS::getApikey(),
            "type" => $type,
            "totp_issuer" => $totpIssuer,
            "totp_user" => $totpUser,
            "phone_number" => $phoneNumber
        ];

        $result = $this->enrollFactorTotpResponseFixture();

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            false,
            $result
        );

        $enrollFactorTotp = $this->mfa->enrollFactor($type, "test", "ricksanchez", "1234567890" );
        $enrollFactorTotpFixture = $this->enrollFactorTotpResponseFixture();

        $this->assertSame($enrollFactorTotpFixture, $enrollFactorTotp);
    }

    public function testChallengeFactor()
    {
        $path = "auth/factors/challenge";
        $params = [
            "client_id" => WorkOS::getClientId(),
            "client_secret" => WorkOS::getApikey(),
            "authentication_factor_id" => $authenticationFactorId,
            "sms_template" => $smsTemplate
        ];

        $result = $this->profileAndTokenResponseFixture();

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            false,
            $result
        );

        $profileAndToken = $this->sso->getProfileAndToken('code');
        $profileFixture = $this->profileFixture();

        $this->assertSame("01DMEK0J53CVMC32CK5SE0KZ8Q", $profileAndToken->accessToken);
        $this->assertSame($profileFixture, $profileAndToken->profile);
    }

    public function testVerifyFactor()
    {
        $path = "auth/factors/Verify";
        $params = [
            "client_id" => WorkOS::getClientId(),
            "client_secret" => WorkOS::getApikey(),
            "type" => $type,
            "totp_issuer" => $totpIssuer,
            "totp_user" => $totpUser,
            "phone_number" => $phoneNumber
        ];

        $result = $this->profileAndTokenResponseFixture();

        $this->mockRequest(
            Client::METHOD_POST,
            $path,
            null,
            $params,
            false,
            $result
        );

        $profileAndToken = $this->sso->getProfileAndToken('code');
        $profileFixture = $this->profileFixture();

        $this->assertSame("01DMEK0J53CVMC32CK5SE0KZ8Q", $profileAndToken->accessToken);
        $this->assertSame($profileFixture, $profileAndToken->profile);
    }

    // Fixtures

    private function enrollFactorTotpResponseFixture()
    {
        return json_encode([
            "object" => "authentication_factor",
            "id" => "auth_factor_01FXNWW32G7F3MG8MYK5D1HJJM",
            "created_at" => "2022-03-08T23:12:20.157Z",
            "updated_at" => "2022-03-08T23:12:20.157Z",
            "type" => "totp",
            "environment_id" => "environment_01EQ1FTYTAZCVYSV4SYSRWRR3A",
            "totp" => [
                "qr_code" => "data:image/png;base64,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",
                "secret" => "JJWBYBLLH5TRKYZEGMREU6DRKFRVMTCV",
                "uri" => "otpauth://totp/testytest:berniesanders?secret=JJWBYBLLH5TRKYZEGMREU6DRKFRVMTCV&issuer=testytest"
            ]
        ]);
    }

    private function enrollFactorSmsResponseFixture()
    {
        return json_encode([
            "object" => "authentication_factor",
            "id" => "auth_factor_01FXK4YXEZEEQ0AYNJE5PA7FQR",
            "created_at" => "2022-03-08T23:12:20.157Z",
            "updated_at" => "2022-03-08T23:12:20.157Z",
            "type" => "sms",
            "environment_id" => "environment_01EQ1FTYTAZCVYSV4SYSRWRR3A",
            "sms" => [
                    "phone_number" => "+7208675309"            
                    ]
        ]);
    }

    private function challengeFactorResponseFicture()
    {
        return json_encode([
            "object" => "authentication_challenge",
            "id" => "auth_challenge_01FXNX3BTZPPJVKF65NNWGRHZJ",
            "created_at" => "2022-03-08T23:16:18.532Z",
            "updated_at" => "2022-03-08T23:16:18.532Z",
            "authentication_factor_id" => "auth_factor_01FXNWW32G7F3MG8MYK5D1HJJM"
        ]);
    }

    private function verifyFactorResponseFicture()
    {
        return json_encode([
            "challenge" => [
                "object" => "authentication_challenge",
                "id" => "auth_challenge_01FXNX3BTZPPJVKF65NNWGRHZJ",
                "created_at" => "2022-03-08T23:16:18.532Z",
                "updated_at" => "2022-03-08T23:16:18.532Z",
                "expires_at" => "2022-02-15T15:36:53.279Z",
                "authentication_factor_id" => "auth_factor_01FXNWW32G7F3MG8MYK5D1HJJM"
        ]
            ]);
    }

    private function profileFixture()
    {
        return [
            "id" => "prof_hen",
            "email" => "hen@papagenos.com",
            "first_name" => "hen",
            "last_name" => "cha",
            "organization_id" => "org_01FG7HGMY2CZZR2FWHTEE94VF0",
            "connection_id" => "conn_01EMH8WAK20T42N2NBMNBCYHAG",
            "connection_type" => "GoogleOAuth",
            "idp_id" => "randomalphanum",
            "raw_attributes" => array(
                "email" => "hen@papagenos.com",
                "first_name" => "hen",
                "last_name" => "cha",
                "ipd_id" => "randomalphanum"
            )
        ];
    }

}