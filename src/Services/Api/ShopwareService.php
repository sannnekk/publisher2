<?php

declare(strict_types=1);

namespace HMnet\Publisher2\Services\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use HMnet\Publisher2\Log\LoggerInterface;
use HMnet\Publisher2\Log\Logger;
use HMnet\Publisher2\Model\Model;
use HMnet\Publisher2\Model\Local\Criteria\Criteria;
use HMnet\Publisher2\Model\Product\ProductMedia;

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

	private int $debugMode = 0;

	private Client $httpClient;
	private LoggerInterface $logger;

	public function __construct(string $apiUrl, string $username, string $password, int $debug = 0)
	{
		$this->debugMode = $debug;
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
			$payload = array_map(fn ($entity) => $entity->serialize(), $entities);

			if ($this->debugMode > 0) {
				$this->logger->debug($entityName, $payload);
			}

			$response = $this->httpClient->post('_action/sync', [
				'headers' => [
					'Authorization' => 'Bearer ' . $this->token
				],
				'json' => [
					[
						'entity' => $entityName,
						'action' => 'upsert',
						'payload' => $payload,
					]
				],
			]);

			$responseCode = $response->getStatusCode();
			//$response = json_decode($response->getBody()->getContents(), true);

			if ($responseCode > 299 || $responseCode < 200) {
				$this->logger->error('Shopware sync failed: bad response code: ' . $responseCode);
				throw new \Exception('Shopware sync failed: bad response code: ' . $responseCode);
			}

			$this->logger->info('Successfully synced ' . count($entities) . ' ' . $entityName . ' entities with Shopware, status code ' . $responseCode);
		} catch (ClientException $e) { // 4xx error
			$response = $e->getResponse();
			$this->logger->error('Shopware sync failed on SW, response: ' . $response->getBody()->getContents());
			throw new \Exception('Shopware sync failed on SW, message: ' . $e->getMessage() . ', code: ' . $e->getCode());
		} catch (\Exception $e) {
			$this->logger->error('Shopware sync failed, message: ' . $e->getMessage() . ', code: ' . $e->getCode());
			throw new \Exception('Shopware sync failed, message: ' . $e->getMessage() . ', code: ' . $e->getCode());
		}
	}

	/**
	 * Delete relations from Shopware
	 * 
	 * @param array<Relation> $relations
	 */
	public function deleteRelations(array $relations): void
	{
		if (count($relations) === 0) {
			return;
		}

		$relationName = $relations[0]::name();

		if ($relationName === null) {
			throw new \Exception('Relation is local only and cannot be synced with Shopware');
		}

		$this->logger->info('Deleting ' . count($relations) . ' ' . $relationName . ' relations from Shopware');

		try {
			$payload = array_map(fn ($entity) => $entity->serialize(), $relations);

			if ($this->debugMode > 0) {
				$this->logger->debug($relationName, $payload);
			}

			$response = $this->httpClient->post('_action/sync', [
				'headers' => [
					'Authorization' => 'Bearer ' . $this->token
				],
				'json' => [
					[
						'entity' => $relationName,
						'action' => 'delete',
						'payload' => $payload,
					]
				],
			]);

			$responseCode = $response->getStatusCode();
			//$response = json_decode($response->getBody()->getContents(), true);

			if ($responseCode > 299 || $responseCode < 200) {
				$this->logger->error('Shopware relation deletion failed: bad response code: ' . $responseCode);
				throw new \Exception('Shopware relation deletion failed: bad response code: ' . $responseCode);
			}

			$this->logger->info('Successfully deleted ' . count($relations) . ' ' . $relationName . ' relations with Shopware, status code ' . $responseCode);
		} catch (ClientException $e) { // 4xx error
			$response = $e->getResponse();
			$this->logger->error('Shopware relation deletion failed on SW, response: ' . $response->getBody()->getContents());
			throw new \Exception('Shopware relation deletion failed on SW, message: ' . $e->getMessage() . ', code: ' . $e->getCode());
		} catch (\Exception $e) {
			$this->logger->error('Shopware relation deletion failed, message: ' . $e->getMessage() . ', code: ' . $e->getCode());
			throw new \Exception('Shopware relation deletion failed, message: ' . $e->getMessage() . ', code: ' . $e->getCode());
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

		if (count($idsToDelete) === 0) {
			$this->logger->info('No orphants to remove from ' . $entityName . ' entities in Shopware');
			return;
		}

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
			throw new \Exception('Shopware sync failed');
		}

		return $ids;
	}

	/**
	 * Get entities from Shopware
	 * 
	 * @param string $entityClass
	 * @param Criteria $criteria
	 * @return array<Model>
	 */
	public function getEntities(string $entityClass, Criteria $criteria): array
	{
		$entities = [];

		if ($entityClass::name() === null) {
			throw new \Exception('Entity is local only and cannot be loaded from Shopware');
		}

		if (!method_exists($entityClass, 'fromSWResponse')) {
			throw new \Exception('Entity should implement \HMnet\Publish2\Model\IConstructableFromSWResponse interface');
		}

		$entityName = $entityClass::name();

		try {
			$response = $this->httpClient->post('search/' . $entityName, [
				'headers' => [
					'Authorization' => 'Bearer ' . $this->token,
					'Accept' => 'application/json',
				],
				'json' => $criteria->serialize(),
			]);

			$responseCode = $response->getStatusCode();
			$response = json_decode($response->getBody()->getContents(), true);

			if ($responseCode > 299 || $responseCode < 200) {
				throw new \Exception('Failed to get entities from Shopware, response code: ' . $responseCode);
			}

			$entities = array_map(fn ($entity) => $entityClass::fromSWResponse($entity), $response['data']);
		} catch (\Exception $e) {
			throw new \Exception('Failed to get entities from Shopware, message: ' . $e->getMessage() . ', code: ' . $e->getCode());
		}

		return $entities;
	}

	/**
	 * Set order status
	 * 
	 * @param string $orderId
	 * @param string $status (values: 'process', 'complete')
	 */
	public function setOrderStatus(string $orderId, string $status): void
	{
		try {
			$response = $this->httpClient->post("_action/order/$orderId/state/$status", [
				'headers' => [
					'Authorization' => 'Bearer ' . $this->token
				]
			]);

			$responseCode = $response->getStatusCode();
			$response = json_decode($response->getBody()->getContents(), true);

			if ($responseCode > 299 || $responseCode < 200) {
				$this->logger->error("Failed to set order status, response code: $responseCode");
				return;
			}

			$this->logger->info("Set order status to $status for order $orderId");
		} catch (\Exception $e) {
			$this->logger->error("Failed to set order status for order $orderId, message: " . $e->getMessage() . ', code: ' . $e->getCode());
		}
	}

	/**
	 * Upload images to Shopware
	 * 
	 * @param array<ProductMedia> $images
	 */
	public function uploadImages(array $images): void
	{
		$uploadedCount = 0;
		$this->logger->info('Uploading ' . count($images) . ' images to Shopware');

		foreach ($images as $image) {
			$uploadedCount = $this->uploadImage($image) ? $uploadedCount + 1 : $uploadedCount;
		}

		$this->logger->info('Successfully uploaded ' . $uploadedCount . ' images to Shopware');
		$this->logger->warning('Failed to upload ' . (count($images) - $uploadedCount) . ' images to Shopware');
	}

	/**
	 * Upload image to Shopware
	 * 
	 * @param ProductMedia $image
	 */
	private function uploadImage(ProductMedia $image): bool
	{
		$id = $image->id();
		$extension = $image->extension();

		try {
			$this->httpClient->post(
				'_action/media/' . $id . '/upload?extension=' . $extension,
				[
					'headers' => [
						'Authorization' => 'Bearer ' . $this->token,
						'extension' => $extension,
						'Content-Type' => 'application/json',
						'Accept' => 'application/json',
					],
					'json' => [
						'url' => $image->url(),
					],
				]
			);

			return true;
		} catch (\Exception $e) {
			//$this->logger->error('Image upload failed, message: ' . $e->getMessage() . ', code: ' . $e->getCode());
			return false;
		}
	}
}
