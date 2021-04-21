<?php

namespace WorkOS;

class DirectorySyncTest extends \PHPUnit\Framework\TestCase
{
    use TestHelper {
        setUp as traitSetUp;
    }

    protected function setUp()
    {
        $this->traitSetUp();

        $this->withApiKeyAndClientId();
        $this->ds = new DirectorySync();
    }

    public function testListDirectories()
    {
        $directoriesPath = "directories";
        $params = [
            "limit" => DirectorySync::DEFAULT_PAGE_SIZE,
            "before" => null,
            "after" => null,
            "domain" => null,
            "search" => null
        ];

        $result = $this->directoriesResponseFixture();

        $this->mockRequest(
            Client::METHOD_GET,
            $directoriesPath,
            null,
            $params,
            true,
            $result
        );

        $directory = $this->directoryFixture();

        list($before, $after, $directories) = $this->ds->listDirectories();
        $this->assertSame($directory, $directories[0]->toArray());
    }

    public function testGetGroup()
    {
        $directoryGroup = "directory_grp_id";
        $groupPath = "directory_groups/${directoryGroup}";

        $result = $this->groupResponseFixture();

        $this->mockRequest(
            Client::METHOD_GET,
            $groupPath,
            null,
            null,
            true,
            $result
        );

        $group = $this->ds->getGroup($directoryGroup);
        $groupFixture = $this->groupFixture();

        $this->assertSame($groupFixture, $group->toArray());
    }

    public function testListGroups()
    {
        $usersPath = "directory_groups";
        $params = [
            "limit" => DirectorySync::DEFAULT_PAGE_SIZE,
            "before" => null,
            "after" => null
        ];

        $result = $this->groupsResponseFixture();

        $this->mockRequest(
            Client::METHOD_GET,
            $usersPath,
            null,
            $params,
            true,
            $result
        );

        $group = $this->groupFixture();

        list($before, $after, $groups) = $this->ds->listGroups();
        $this->assertSame($group, $groups[0]->toArray());
    }

    public function testGetUser()
    {
        $directoryUser = "directory_usr_id";
        $userPath = "directory_users/${directoryUser}";

        $result = $this->userResponseFixture();

        $this->mockRequest(
            Client::METHOD_GET,
            $userPath,
            null,
            null,
            true,
            $result
        );

        $user = $this->ds->getUser($directoryUser);
        $userFixture = $this->userFixture();

        $this->assertSame($userFixture, $user->toArray());
    }

    public function testListUsers()
    {
        $usersPath = "directory_users";
        $params = [
            "limit" => DirectorySync::DEFAULT_PAGE_SIZE,
            "before" => null,
            "after" => null
        ];

        $result = $this->usersResponseFixture();

        $this->mockRequest(
            Client::METHOD_GET,
            $usersPath,
            null,
            $params,
            true,
            $result
        );

        $user = $this->userFixture();

        list($before, $after, $users) = $this->ds->listUsers();
        $this->assertSame($user, $users[0]->toArray());
    }

    public function testDeleteDirectory()
    {
        $directory = "directory_id";
        $directoryPath = "directories/${directory}";
        $responseCode = 204;

        $this->mockRequest(
            Client::METHOD_DELETE,
            $directoryPath,
            null,
            null,
            true,
            null,
            null,
            $responseCode
        );

        $response = $this->sso->deleteConnection($connection);
        $this->assertSame(204, $responseCode);
    }


    // Fixtures

    private function directoriesResponseFixture()
    {
        return json_encode([
            "data" => [
                [
                    "id" => "directory_id",
                    "environment_id" => "environment_123",
                    "external_key" => "fried-chicken",
                    "state" => "linked",
                    "type" => "gsuite directory",
                    "name" => "Ri Jeong Hyeok",
                    "bearer_token" => null,
                    "domain" => "crashlandingonyou.com",
                ]
            ],
            "listMetadata" => [
                "before" => null,
                "after" => null
            ],
        ]);
    }

    private function directoryFixture()
    {
        return [
            "id" => "directory_id",
            "environmentId" => "environment_123",
            "externalKey" => "fried-chicken",
            "state" => "linked",
            "type" => "gsuite directory",
            "name" => "Ri Jeong Hyeok",
            "domain" => "crashlandingonyou.com",
        ];
    }

    private function groupsResponseFixture()
    {
        return json_encode([
            "data" => [
                [
                    "name" => "Developers",
                    "id" => "directory_grp_id"
                ]
            ],
            "listMetadata" => [
                "before" => null,
                "after" => null
            ],
        ]);
    }

    private function groupResponseFixture()
    {
        return json_encode([
            "id" => "directory_grp_id",
            "name" => "Developers"
        ]);
    }

    private function groupFixture()
    {
        return [
            "id" => "directory_grp_id",
            "name" => "Developers"
        ];
    }

    private function usersResponseFixture()
    {
        return json_encode([
            "listMetadata" => [
                "before" => null,
                "after" => null
            ],
            "data" => [
                [
                    "username" => "yoon@seri.com",
                    "state" => "active",
                    "last_name" => "Seri",
                    "first_name" => "Yoon",
                    "emails" => [
                        [
                            "primary" => true,
                            "type" => "work",
                            "value" => "yoon@seri.com"
                        ]
                    ],
                    "raw_attributes" => [
                        "schemas" => ["urn:scim:schemas:core:1.0"],
                        "name"=> [
                            "familyName" => "Seri",
                            "givenName" => "Yoon"
                        ],
                        "externalId" => "external-id",
                        "locale" => "en_US",
                        "userName" => "yoon@seri.com",
                        "id" => "directory_usr_id",
                        "displayName" => "Yoon Seri",
                        "active" => true,
                        "groups" => [],
                        "meta" => [
                            "created" => "2020-02-21T00:32:14.443Z",
                            "version" => "7ff066f75718e21a521c269ae7eafce474ae07c1",
                            "lastModified" => "2020-02-21T00:36:44.638Z",
                        ],
                        "emails" => [
                            [
                                "value" => "yoon@seri.com",
                                "type" => "work",
                                "primary" => true
                            ]
                        ],
                    ],
                    "id" => "directory_usr_id"
                ]
            ]
        ]);
    }

    private function userResponseFixture()
    {
        return json_encode([
            "username" => "yoon@seri.com",
            "state" => "active",
            "last_name" => "Seri",
            "first_name" => "Yoon",
            "emails" => [
                [
                    "primary" => true,
                    "type" => "work",
                    "value" => "yoon@seri.com"
                ]
            ],
            "raw_attributes" => [
                "schemas" => ["urn:scim:schemas:core:1.0"],
                "name"=> [
                    "familyName" => "Seri",
                    "givenName" => "Yoon"
                ],
                "externalId" => "external-id",
                "locale" => "en_US",
                "userName" => "yoon@seri.com",
                "id" => "directory_usr_id",
                "displayName" => "Yoon Seri",
                "active" => true,
                "groups" => [],
                "meta" => [
                    "created" => "2020-02-21T00:32:14.443Z",
                    "version" => "7ff066f75718e21a521c269ae7eafce474ae07c1",
                    "lastModified" => "2020-02-21T00:36:44.638Z",
                ],
                "emails" => [
                    [
                        "value" => "yoon@seri.com",
                        "type" => "work",
                        "primary" => true
                    ]
                ],
            ],
            "id" => "directory_usr_id"
        ]);
    }

    private function userFixture()
    {
        return [
            "id" => "directory_usr_id",
            "rawAttributes" => [
                "schemas" => ["urn:scim:schemas:core:1.0"],
                "name"=> [
                    "familyName" => "Seri",
                    "givenName" => "Yoon"
                ],
                "externalId" => "external-id",
                "locale" => "en_US",
                "userName" => "yoon@seri.com",
                "id" => "directory_usr_id",
                "displayName" => "Yoon Seri",
                "active" => true,
                "groups" => [],
                "meta" => [
                    "created" => "2020-02-21T00:32:14.443Z",
                    "version" => "7ff066f75718e21a521c269ae7eafce474ae07c1",
                    "lastModified" => "2020-02-21T00:36:44.638Z",
                ],
                "emails" => [
                    [
                        "value" => "yoon@seri.com",
                        "type" => "work",
                        "primary" => true
                    ]
                ],
            ],
            "firstName" => "Yoon",
            "emails" => [
                [
                    "primary" => true,
                    "type" => "work",
                    "value" => "yoon@seri.com"
                ]
            ],
            "username" => "yoon@seri.com",
            "lastName" => "Seri",
            "state" => "active"
        ];
    }
}
