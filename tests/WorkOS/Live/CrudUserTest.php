<?php

namespace WorkOS\Live;

class CrudUserTest extends \PHPUnit\Framework\TestCase
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
        print "Create a user to test Update \n";
        $rnd = self::rndStr();
        $emailAuth = "jb+{$rnd}@uselessresistance.com";
        $pwdAuth = "x^T!V23UN1@V";

        $fn = "Fn {$rnd}";
        $ln = "Ln {$rnd}";
        $emailVerified = true;
        $user = $this->userManagement->createUser($emailAuth, $pwdAuth, $fn, $ln, $emailVerified);
        var_dump($user);

        print "Update a user to test Update \n";
        $response = $this->userManagement->updateUser($user->id, "New FN", "New LN", false, "new_Password#$@12");
        var_dump($response);

        print "Update a pwd hash \n";
        $response = $this->userManagement->updateUser($user->id, null, null, null, null, "$2b$10$//DXiVVE59p7G5k/4Klx/ezF7BI42QZKmoOD0NDvUuqxRE5bFFBLy", "bcrypt");
        var_dump($response);

        print "Get user \n";
        $response = $this->userManagement->getUser($user->id);
        var_dump($response);

        print "List user \n";
        $response = $this->userManagement->listUsers();
        var_dump($response);

        print "List user with email\n";
        $response = $this->userManagement->listUsers($user->email);
        var_dump($response);

        print "Delete user \n";
        $response = $this->userManagement->deleteUser($user->id);
        var_dump($response);
    }

}
