<?php

namespace WorkOS\Live;

class CrudInvitationTest extends \PHPUnit\Framework\TestCase
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
        print "Create a user to test Invitation \n";
        $rnd = self::rndStr();
        $emailAuth = "jb+{$rnd}@uselessresistance.com";
        $pwdAuth = "x^T!V23UN1@V";

        $fn = "Fn {$rnd}";
        $ln = "Ln {$rnd}";
        $emailVerified = true;
        $user = $this->userManagement->createUser($emailAuth, $pwdAuth, $fn, $ln, $emailVerified);
        var_dump($user);

        print "Send invitation \n";
        $emailInvite = "jb+{$rnd}@workos.com";
        $response = $this->userManagement->sendInvitation($emailInvite, self::ORG_ID, 1, $user->id);
        var_dump($response);
        $invitationId = $response->id;

        print "Get invitation \n";
        $response = $this->userManagement->getInvitation($invitationId);
        var_dump($response);

        print "List invitations by email\n";
        $response = $this->userManagement->listInvitations($emailInvite);
        var_dump($response);

        print "List invitations by org id\n";
        $response = $this->userManagement->listInvitations(null, self::ORG_ID);
        var_dump($response);

        print "Revoke invitation \n";
        $response = $this->userManagement->revokeInvitation($invitationId);
        var_dump($response);

        print "Get invitation \n";
        $response = $this->userManagement->getInvitation($invitationId);
        var_dump($response);

    }

}
