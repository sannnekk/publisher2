<?php

declare(strict_types=1);

namespace HMnet\Publisher2\Services\Api;

use GuzzleHttp\Client;
use HMnet\Publisher2\Log\LoggerInterface;
use HMnet\Publisher2\Log\Logger;
use HMnet\Publisher2\Model\Product\ProductMedia;
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
	private LoggerInterface $logger;

	public function __construct(string $apiUrl, string $username, string $password)
	{
		$this->apiUrl = $apiUrl;
		$this->authData['username'] = $username;
		$this->authData['password'] = $password;

		$this->logger = new Logger(1);

		$this->httpClient = new Client([
			'base_uri' => $this->apiUrl,
			'timeout' => 60,
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
			$this->logger->info('Authenticating with Shopware');
			$response = $this->httpClient->post('oauth/token', [
				'json' => $this->authData,
			]);

			$this->token = json_decode($response->getBody()->getContents(), true)['access_token'];
			$this->logger->info('Successfully authenticated with Shopware');
		} catch (\Exception $e) {
			throw new \Exception('Shopware auth failed', 0, $e);
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

		$this->logger->info('Syncing ' . count($entities) . ' ' . $entityName . ' entities with Shopware');

		try {
			$response = $this->httpClient->post('_action/sync', [
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
				$this->logger->error('Shopware sync failed: bad response code: ' . $responseCode);
				throw new \Exception('Shopware sync failed: bad response code: ' . $responseCode);
			}

			$this->logger->info('Successfully synced ' . count($entities) . ' ' . $entityName . ' entities with Shopware, status code ' . $responseCode);
		} catch (\Exception $e) {
			$this->logger->error('Shopware sync failed, message: ' . $e->getMessage() . ', code: ' . $e->getCode());
			throw new \Exception('Shopware sync failed, message: ' . $e->getMessage() . ', code: ' . $e->getCode());
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

		$this->logger->info('Removing orphants from ' . $entityName . ' entities in Shopware');

		$idsToDelete = $this->getIdsToDelete(
			$entityName,
			array_map(fn ($entity) => $entity->id ?? $entity->id(), $entitiesToKeep),
			(int) $_ENV['REMOVE_ORPHANTS_LIMIT'] ?? 100
		);

		$this->logger->info('Removing ' . count($idsToDelete) . ' orphants from ' . $entityName . ' entities in Shopware');

		try {
			$response = $this->httpClient->post('_action/sync', [
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
				$this->logger->error('Removal of orphans failed, status code ' . $responseCode);
				throw new \Exception('Removal of orphans failed');
			}

			$this->logger->info('Successfully removed ' . count($idsToDelete) . ' orphants from ' . $entityName . ' entities in Shopware, status code ' . $responseCode);
		} catch (\Exception $e) {
			$this->logger->error('Removal of orphans failed, message: ' . $e->getMessage() . ', code: ' . $e->getCode());
			throw new \Exception('Removal of orphans failed');
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

	/**
	 * Get ids of entities
	 * 
	 * @param string $entityName
	 * @param int $limit
	 * @param int $page
	 * @return array<string>
	 */
	private function getIds(string $entityName, int $limit, int $page): array
	{
		$ids = [];

		try {
			$response = $this->httpClient->get($entityName . '?limit=' . $limit . '&page=' . $page, [
				'headers' => [
					'Authorization' => 'Bearer ' . $this->token,
					'Accept' => 'application/json',
				],
			]);

			$responseCode = $response->getStatusCode();
			$response = json_decode($response->getBody()->getContents(), true);

			if ($responseCode > 299 || $responseCode < 200) {
				throw new \Exception('Shopware sync failed');
			}

			$ids = array_map(fn ($entity) => $entity['id'], $response['data']);
		} catch (\Exception $e) {
			throw $e;
			throw new \Exception('Shopware sync failed');
		}

		return $ids;
	}

	/**
	 * Upload images to Shopware
	 * 
	 * @param array<ProductMedia> $images
	 */
	public function uploadImages(array $images): void
	{
		$this->logger->info('Uploading ' . count($images) . ' images to Shopware');
		foreach ($images as $image) {
			$this->uploadImage($image);
		}
	}

	/**
	 * Upload image to Shopware
	 * 
	 * @param ProductMedia $image
	 */
	private function uploadImage(ProductMedia $image): void
	{
		$id = $image->id();

		try {
			echo 'URL: ' . $image->url() . PHP_EOL;
			die;
			$this->httpClient->post(
				'_action/media/' . $id . '/upload',
				[
					'headers' => [
						'Authorization' => 'Bearer ' . $this->token,
					],
					'json' => [
						'url' => $image->url(),
					],
				]
			);
		} catch (\Exception $e) {
			$this->logger->error('Image upload failed, message: ' . $e->getMessage() . ', code: ' . $e->getCode());
			return;
		}
	}
}
