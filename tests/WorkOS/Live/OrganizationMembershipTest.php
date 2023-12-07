<?php

namespace WorkOS\Live;

class OrganizationMembershipTest extends \PHPUnit\Framework\TestCase
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
        print "Create a user to test memberships \n";
        $rnd = self::rndStr();
        $emailAuth = "jb+{$rnd}@uselessresistance.com";
        $pwdAuth = "x^T!V23UN1@V";
        $names = $this->randomName();
        $fn = $names[0];
        $ln = $names[1];
        $emailVerified = true;
        $user = $this->userManagement->createUser($emailAuth, $pwdAuth, $fn, $ln, $emailVerified);
        var_dump($user);

        $userID = $user->id;

        // Org membership
        print "Add user to org \n";
        $response = $this->userManagement->createOrganizationMembership($userID, self::ORG_ID);
        var_dump($response);
        $omId = $response->id;

        print "fetch organization membership \n";
        $response = $this->userManagement->getOrganizationMembership($omId);
        var_dump($response);

        print "List membership of user \n";
        $response = $this->userManagement->listOrganizationMemberships($userID, null);
        var_dump($response);

        print "List membership of org \n";
        $response = $this->userManagement->listOrganizationMemberships(null, self::ORG_ID);
        var_dump($response);

        print "List membership of both org and user \n";
        $response = $this->userManagement->listOrganizationMemberships($userID, self::ORG_ID);
        var_dump($response);

        print "Delete membership \n";
        $response = $this->userManagement->deleteOrganizationMembership($omId);
        var_dump($response);

        $this->expectException(\WorkOS\Exception\NotFoundException::class);
        print "fetch organization membership again";
        $response = $this->userManagement->getOrganizationMembership($omId);
        var_dump($response);

    }

}
