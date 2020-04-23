<?php

namespace WorkOS;

class DirectorySync
{
    const DEFAULT_PAGE_SIZE = 10;

    public function listDirectories(
        $domain = null,
        $search = null,
        $limit = self::DEFAULT_PAGE_SIZE,
        $before = null,
        $after = null
    ) {
        $directoriesPath = "directories";
        $params = [
            "limit" => $limit,
            "before" => $before,
            "after" => $after,
            "domain" => $domain,
            "search" => $search
        ];

        $response = Client::request(
            Client::METHOD_GET,
            $directoriesPath,
            null,
            $params,
            true
        );

        $directories = [];
        [$before, $after] = Util\Request::parsePaginationArgs($response);
        foreach ($response["data"] as $response) {
            \array_push($directories, Resource\Directory::constructFromResponse($response));
        }

        return [$before, $after, $directories];
    }

    public function listGroups(
        $directory = null,
        $user = null,
        $limit = self::DEFAULT_PAGE_SIZE,
        $before = null,
        $after = null
    ) {
        $groupsPath = "directory_groups";

        $params = [
            "limit" => $limit,
            "before" => $before,
            "after" => $after
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
        [$before, $after] = Util\Request::parsePaginationArgs($response);
        foreach ($response["data"] as $response) {
            \array_push($groups, Resource\DirectoryGroup::constructFromResponse($response));
        }

        return [$before, $after, $groups];
    }

    public function listUsers(
        $directory = null,
        $group = null,
        $limit = self::DEFAULT_PAGE_SIZE,
        $before = null,
        $after = null
    ) {
        $usersPath = "directory_users";

        $params = [
            "limit" => $limit,
            "before" => $before,
            "after" => $after
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
        [$before, $after] = Util\Request::parsePaginationArgs($response);
        foreach ($response["data"] as $response) {
            \array_push($users, Resource\DirectoryUser::constructFromResponse($response));
        }

        return [$before, $after, $users];
    }

    public function getUser($directory, $directoryUser)
    {
        $userPath = "directories/${directory}/users/${directoryUser}";

        $response = Client::request(
            Client::METHOD_GET,
            $userPath,
            null,
            null,
            true
        );

        return Resource\DirectoryUser::constructFromResponse($response);
    }
}
