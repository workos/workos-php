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

        return [
            $response["listMetadata"]["before"],
            $response["listMetadata"]["after"],
            $response["data"]
        ];
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

        return [
            $response["listMetadata"]["before"],
            $response["listMetadata"]["after"],
            $response["data"]
        ];
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

        return [
            $response["listMetadata"]["before"],
            $response["listMetadata"]["after"],
            $response["data"]
        ];
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

        return $response;
    }
}
