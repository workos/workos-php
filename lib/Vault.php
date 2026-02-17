<?php

namespace WorkOS;

/**
 * Class Vault.
 *
 * This class facilitates the use of WorkOS Vault operations.
 */
class Vault
{
    public const DEFAULT_PAGE_SIZE = 10;

    /**
     * Get a Vault Object.
     *
     * @param string $vaultObjectId The unique identifier for the vault object.
     *
     * @throws Exception\WorkOSException
     *
     * @return Resource\VaultObject
     */
    public function getVaultObject($vaultObjectId)
    {
        $vaultObjectPath = "vault/v1/kv/{$vaultObjectId}";

        $response = Client::request(Client::METHOD_GET, $vaultObjectPath, null, null, true);

        return Resource\VaultObject::constructFromResponse($response);
    }

    /**
     * List Vault Objects.
     *
     * @param int $limit Maximum number of records to return
     * @param null|string $before Vault Object ID to look before
     * @param null|string $after Vault Object ID to look after
     * @param Resource\Order $order The Order in which to paginate records
     *
     * @return Resource\PaginatedResource A paginated resource containing before/after cursors and vault_objects array.
     *         Supports: [$before, $after, $objects] = $result, ["vault_objects" => $objects] = $result, $result->vault_objects
     *
     * @throws Exception\WorkOSException
     */
    public function listVaultObjects(
        $limit = self::DEFAULT_PAGE_SIZE,
        $before = null,
        $after = null,
        $order = null
    ) {
        $vaultObjectsPath = "vault/v1/kv";
        $params = [
            "limit" => $limit,
            "before" => $before,
            "after" => $after,
            "order" => $order
        ];

        $response = Client::request(
            Client::METHOD_GET,
            $vaultObjectsPath,
            null,
            $params,
            true
        );

        return Resource\PaginatedResource::constructFromResponse($response, Resource\VaultObject::class, 'vault_objects');
    }




}
