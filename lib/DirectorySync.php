<?php

namespace WorkOS;

/**
 * Class DirectorySync.
 *
 * This class facilitates the user of WorkOS Directory Sync.
 */
class DirectorySync
{
    public const DEFAULT_PAGE_SIZE = 10;

    /**
     * List Directories.
     *
     * @param null|string $domain Domain of a Directory
     * @param null|string $search Searchable text for a Directory
     * @param int $limit Maximum number of records to return
     * @param null|string $before Directory ID to look before
     * @param null|string $after Directory ID to look after
     * @param null|string $organizationId Unique ID for an organization
     * @param \WorkOS\Resource\Order $order The Order in which to paginate records
     *
     * @throws Exception\WorkOSException
     *
     * @return array An array containing the following:
     *      null|string Directory ID to use as before cursor
     *      null|string Directory ID to use as after cursor
     *      array \WorkOS\Resource\Directory instances
     */
    public function listDirectories(
        $domain = null,
        $search = null,
        $limit = self::DEFAULT_PAGE_SIZE,
        $before = null,
        $after = null,
        $organizationId = null,
        $order = null
    ) {
        $directoriesPath = "directories";
        $params = [
            "limit" => $limit,
            "before" => $before,
            "after" => $after,
            "domain" => $domain,
            "search" => $search,
            "organization_id" => $organizationId,
            "order" => $order
        ];

        $response = Client::request(
            Client::METHOD_GET,
            $directoriesPath,
            null,
            $params,
            true
        );

        $directories = [];
        list($before, $after) = Util\Request::parsePaginationArgs($response);
        foreach ($response["data"] as $responseData) {
            \array_push($directories, Resource\Directory::constructFromResponse($responseData));
        }

        return [$before, $after, $directories];
    }

    /**
     * List Directory Groups.
     *
     * @param null|string $directory Directory ID
     * @param null|string $user Directory User ID
     * @param int $limit Maximum number of records to return
     * @param null|string $before Directory Group ID to look before
     * @param null|string $after Directory Group ID to look after
     * @param \WorkOS\Resource\Order $order The Order in which to paginate records
     *
     * @throws Exception\WorkOSException
     *
     * @return array An array containing the following:
     *      null|string Directory Group ID to use as before cursor
     *      null|string Directory Group ID to use as after cursor
     *      array \WorkOS\Resource\DirectoryGroup instances
     */
    public function listGroups(
        $directory = null,
        $user = null,
        $limit = self::DEFAULT_PAGE_SIZE,
        $before = null,
        $after = null,
        $order = null
    ) {
        $groupsPath = "directory_groups";

        $params = [
            "limit" => $limit,
            "before" => $before,
            "after" => $after,
            "order" => $order
        ];
        if ($directory) {
            $params["directory"] = $directory;
        }
        if ($user) {
            $params["user"] = $group;
        }

        $response = Client::request(
            Client::METHOD_GET,
            $groupsPath,
            null,
            $params,
            true
        );

        $groups = [];
        list($before, $after) = Util\Request::parsePaginationArgs($response);
        foreach ($response["data"] as $response) {
            \array_push($groups, Resource\DirectoryGroup::constructFromResponse($response));
        }

        return [$before, $after, $groups];
    }

    /**
     * Get a Directory Group.
     *
     * @param string $directoryGroup Directory Group ID
     *
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\DirectoryGroup
     */
    public function getGroup($directoryGroup)
    {
        $groupPath = "directory_groups/{$directoryGroup}";

        $response = Client::request(
            Client::METHOD_GET,
            $groupPath,
            null,
            null,
            true
        );

        return Resource\DirectoryGroup::constructFromResponse($response);
    }

    /**
     * List Directory Users.
     *
     * @param null|string $directory Directory ID
     * @param null|string $group Directory Group ID
     * @param int $limit Maximum number of records to return
     * @param null|string $before Directory User ID to look before
     * @param null|string $after Directory User ID to look after
     * @param \WorkOS\Resource\Order $order The Order in which to paginate records
     *
     * @throws Exception\WorkOSException
     *
     * @return array An array containing the following:
     *      null|string Directory User ID to use as before cursor
     *      null|string Directory User ID to use as after cursor
     *      array \WorkOS\Resource\DirectoryUser instances
     */
    public function listUsers(
        $directory = null,
        $group = null,
        $limit = self::DEFAULT_PAGE_SIZE,
        $before = null,
        $after = null,
        $order = null
    ) {
        $usersPath = "directory_users";

        $params = [
            "limit" => $limit,
            "before" => $before,
            "after" => $after,
            "order" => $order
        ];
        if ($directory) {
            $params["directory"] = $directory;
        }
        if ($group) {
            $params["group"] = $group;
        }

        $response = Client::request(
            Client::METHOD_GET,
            $usersPath,
            null,
            $params,
            true
        );

        $users = [];
        list($before, $after) = Util\Request::parsePaginationArgs($response);
        foreach ($response["data"] as $response) {
            \array_push($users, Resource\DirectoryUser::constructFromResponse($response));
        }

        return [$before, $after, $users];
    }

    /**
     * Get a Directory User.
     *
     * @param string $directoryUser Directory User ID
     *
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\DirectoryUser
     */
    public function getUser($directoryUser)
    {
        $userPath = "directory_users/{$directoryUser}";

        $response = Client::request(
            Client::METHOD_GET,
            $userPath,
            null,
            null,
            true
        );

        return Resource\DirectoryUser::constructFromResponse($response);
    }

    /**
     * Delete a Directory.
     *
     * @param string $directory Directory ID
     *
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\Response
     */
    public function deleteDirectory($directory)
    {
        $directoryPath = "directories/{$directory}";

        $response = Client::request(
            Client::METHOD_DELETE,
            $directoryPath,
            null,
            null,
            true
        );

        return $response;
    }

    /**
     * Get a Directory.
     *
     * @param string $directory WorkOS directory ID
     *
     * @throws Exception\WorkOSException
     *
     * @return \WorkOS\Resource\Directory
     */
    public function getDirectory($directory)
    {
        $directoriesPath = "directories/{$directory}";

        $response = Client::request(Client::METHOD_GET, $directoriesPath, null, null, true);

        return Resource\Directory::constructFromResponse($response);
    }
}
