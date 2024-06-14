<?php

declare(strict_types=1);

namespace HMnet\Publisher2\Services\Api;

use GuzzleHttp\Client;
use HMnet\Publisher2\Model\Model;

class ShopwareService
{
	private string $token;
	private string $apiUrl;
	private array $authData = [
		"client_id" => "administration",
		"grant_type" => "password",
		"scopes" => "write",
		"username" => null,
		"password" => null,
	];

	private Client $httpClient;

	public function __construct(string $apiUrl, string $username, string $password)
	{
		$this->apiUrl = $apiUrl;
		$this->authData['username'] = $username;
		$this->authData['password'] = $password;

		$this->httpClient = new Client([
			'base_uri' => $this->apiUrl,
			'timeout' => 30,
		]);

		$this->auth();
	}

	/**
	 * Authenticate with Shopware
	 * 
	 */
	private function auth(): void
	{
		try {
			$response = $this->httpClient->post('/oauth/token', [
				'json' => $this->authData,
			]);

			$this->token = json_decode($response->getBody()->getContents(), true)['access_token'];
		} catch (\Exception $e) {
			throw new \Exception('Shopware auth failed');
		}
	}

	/**
	 * Sync entities with Shopware
	 * 
	 * @param array<Model> $entities
	 */
	public function syncEntities(array $entities): void
	{
		if (count($entities) === 0) {
			return;
		}

		$entityName = $entities[0]::name();

		if ($entityName === null) {
			throw new \Exception('Entity is local only and cannot be synced with Shopware');
		}

		try {
			$response = $this->httpClient->post('/_action/sync', [
				'headers' => [
					'Authorization' => 'Bearer ' . $this->token,
				],
				'json' => [
					[
						'entity' => $entityName,
						'action' => 'upsert',
						'payload' => array_map(fn ($entity) => $entity->serialize(), $entities),
					]
				],
			]);

			$responseCode = $response->getStatusCode();
			$response = json_decode($response->getBody()->getContents(), true);

			if ($responseCode > 299 || $responseCode < 200) {
				throw new \Exception('Shopware sync failed');
			}
		} catch (\Exception $e) {
			throw new \Exception('Shopware sync failed');
		}
	}

	/**
	 * Remove entites that are not in the list of entities to keep
	 * 
	 * @param array<Model> $entitiesToKeep
	 */
	public function removeOrphants(array $entitiesToKeep): void
	{
		if (count($entitiesToKeep) === 0) {
			return;
		}

		$entityName = $entitiesToKeep[0]::name();

		if ($entityName === null) {
			throw new \Exception('Entity is local only and cannot be synced with Shopware');
		}

		$idsToDelete = $this->getIdsToDelete(
			$entityName,
			array_map(fn ($entity) => $entity->id ?? $entity->id(), $entitiesToKeep),
			100
		);

		try {
			$response = $this->httpClient->post('/_action/sync', [
				'headers' => [
					'Authorization' => 'Bearer ' . $this->token,
				],
				'json' => [
					[
						'entity' => $entityName,
						'action' => 'delete',
						'payload' => array_map(fn ($id) => ['id' => $id], $idsToDelete),
					]
				],
			]);

			$responseCode = $response->getStatusCode();
			$response = json_decode($response->getBody()->getContents(), true);

			if ($responseCode > 299 || $responseCode < 200) {
				throw new \Exception('Shopware sync failed');
			}
		} catch (\Exception $e) {
			throw new \Exception('Shopware sync failed');
		}
	}

	/**
	 * Get ids of all entities
	 * 
	 * @param string $entityName
	 * @param int $limit
	 * @param int $page
	 * 
	 * @return array<int>
	 */
	private function getIdsToDelete(string $entityName, array $idsToKeep, int $limit): array
	{
		$idsToDelete = [];

		$page = 1;
		$limit = 500;
		$ids = $this->getIds($entityName, $limit, $page);

		while (count($ids) > 0) {
			$idsToDelete = array_merge($idsToDelete, array_diff($ids, $idsToKeep));

			$page++;
			$ids = $this->getIds($entityName, $limit, $page);
		}

		return $idsToDelete;
	}

	private function getIds(string $entityName, int $limit, int $page): array
	{
		$ids = [];

		try {
			$response = $this->httpClient->get($entityName . '?limit=' . $limit . '&page=' . $page, [
				'headers' => [
					'Authorization' => 'Bearer ' . $this->token,
				],
			]);

			$responseCode = $response->getStatusCode();
			$response = json_decode($response->getBody()->getContents(), true);

			if ($responseCode > 299 || $responseCode < 200) {
				throw new \Exception('Shopware sync failed');
			}

			$ids = array_map(fn ($entity) => $entity['id'], $response['data']);
		} catch (\Exception $e) {
			throw new \Exception('Shopware sync failed');
		}

		return $ids;
	}

	/**
	 * Upload images to Shopware
	 * 
	 * @param array<Image> $images
	 */
	public function uploadImages(array $images): void
	{
	}
}
