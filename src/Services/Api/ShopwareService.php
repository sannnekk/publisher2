<?php

declare(strict_types=1);

namespace HMnet\Publisher2\Services\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use HMnet\Publisher2\Log\LoggerInterface;
use HMnet\Publisher2\Log\Logger;
use HMnet\Publisher2\Model\Collection\CategoryCollection;
use HMnet\Publisher2\Model\Local\Criteria\Filter;
use HMnet\Publisher2\Model\Model;
use HMnet\Publisher2\Model\Local\Criteria\Criteria;
use HMnet\Publisher2\Model\Product\ProductMedia;
use HMnet\Publisher2\Model\Search\SearchFilter;
use HMnet\Publisher2\Model\Search\SearchType;
use HMnet\Publisher2\Model\Category\Category;

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

	private int $debugMode = 1;

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
			$payload = array_map(fn($entity) => $entity->serialize(), $entities);

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
		} catch (ServerException $e) {
			$this->logger->error('Shopware sync failed on server, response: ' . $e->getMessage() . ', code: ' . $e->getCode());
			$this->logger->error('Request: ' . $e->getRequest()->getBody()->getContents());
			$this->logger->error('Response: ' . $e->getResponse()->getBody()->getContents());
			throw new \Exception('Shopware sync failed on server, response: ' . $e->getMessage() . ', code: ' . $e->getCode());
		} catch (\Exception $e) {
			$this->logger->error('Shopware sync failed in php, message: ' . $e->getMessage() . ', code: ' . $e->getCode());
			throw new \Exception('Shopware sync failed in php, message: ' . $e->getMessage() . ', code: ' . $e->getCode());
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
			array_map(fn($entity) => $entity->id ?? $entity->id(), $entitiesToKeep),
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
						'payload' => array_map(fn($id) => ['id' => $id], $idsToDelete),
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
	 * Remove categories that are not in the list of categories to keep
	 */
	public function removeOrphantCategories(array $categories): void
	{
		if (count($categories) === 0) {
			return;
		}

		$idsToDelete = $this->getCategoryIdsToDelete(
			array_map(fn($entity) => $entity->id ?? $entity->id(), $categories)
		);

		if (count($idsToDelete) === 0) {
			$this->logger->info('No orphant categories to remove from Shopware');
			return;
		}

		$this->logger->info('Removing ' . count($idsToDelete) . ' orphant categories from Shopware');

		try {
			$response = $this->httpClient->post('_action/sync', [
				'headers' => [
					'Authorization' => 'Bearer ' . $this->token,
				],
				'json' => [
					[
						'entity' => 'category',
						'action' => 'delete',
						'payload' => array_values(array_map(fn($id) => ['id' => $id], $idsToDelete)),
					]
				],
			]);

			$this->logger->info('Sent request to remove orphant categories from Shopware, waiting for response...');
			

			$responseCode = $response->getStatusCode();
			$response = json_decode($response->getBody()->getContents(), true);

			if ($responseCode > 299 || $responseCode < 200) {
				$this->logger->error('Removal of orphans failed, status code ' . $responseCode . ', response: ' . json_encode($response));
				throw new \Exception('Removal of orphans failed');
			}

			$this->logger->info('Successfully removed ' . count($idsToDelete) . ' orphant categories from Shopware, status code ' . $responseCode);
		} catch (\Exception $e) {
			$this->logger->error('Removal of orphant categories failed, message: ' . $e->getMessage() . ', code: ' . $e->getCode());
			throw new \Exception('Removal of orphant categories failed, message: ' . $e->getMessage() . ', code: ' . $e->getCode());
		}
	}

	/**
	 * Get ids of categories to delete
	 * 
	 * @param array<string> $categoryIds
	 * @return array<string>
	 */
	public function getCategoryIdsToDelete(array $categoryIdsToKeep): array
	{
		$idsToDelete = [];
		$filters = [];

		foreach (explode('|', $_ENV['MANAGEABLE_CATS_LVL1']) as $catId) {
			$filters[] = new Filter('contains', 'path', $catId);
		}

		$page = 1;
		$limit = 500;
		$filter = new Filter('multi', null, null, null, 'or', $filters);

		$categories = $this->getEntities(Category::class, new Criteria($page, $limit, [$filter]));

		while (count($categories) > 0) {
			$idsToDelete = array_merge($idsToDelete, array_map(fn($cat) => $cat->id, $categories));
			$page++;
			$categories = $this->getEntities(Category::class, new Criteria($page, $limit, [$filter]));
		}

		return array_diff($idsToDelete, $categoryIdsToKeep);
	}

	/**
	 * Get ids of entities that are no more resepresent in the source to delete them from SW
	 * 
	 * @param string $entityName
	 * @param array $idsToKeep
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
	 * Remove all products from categories that are synced with SW
	 */
	public function removeAllProductsFromCategories(CategoryCollection $categories): void
	{
		$this->logger->info("Removing all products from synced categories in Shopware");

		$categories = $categories->toArray();

		$this->logger->info("Removing products from " . count($categories) . " categories in Shopware");

		$deleteCount = 0;

		do {
			$deleted = $this->genericDelete('product_category', [
				new SearchFilter(SearchType::EQUALS_ANY, 'category.id', array_map(fn($category) => $category->id, $categories)),
			]);

			$deleteCount += $deleted;
		} while ($deleted > 0);

		$this->logger->info("Removed $deleteCount products from categories in Shopware");
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
				throw new \Exception('Shopware sync failed, response: ' . $responseCode . ''. $response->getBody()->getContents());
			}

			$ids = array_map(fn($entity) => $entity['id'], $response['data']);
		} catch (\Exception $e) {
			throw $e;
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

			$entities = array_map(fn($entity) => $entityClass::fromSWResponse($entity), $response['data']);
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
	 * Delete entities from Shopware
	 * 
	 * @param array<Model> $entities
	 */
	public function deleteEntities(array $entities): void
	{
		if (count($entities) === 0) {
			return;
		}

		$entityName = $entities[0]::name();

		if ($entityName === null) {
			throw new \Exception('Entity is local only and cannot be synced with Shopware');
		}

		$this->logger->info('Deleting ' . count($entities) . ' ' . $entityName . ' entities from Shopware');

		try {
			$payload = array_map(fn($entity) => $entity->serialize(), $entities);
			$payload = array_map(fn($entity) => ['id' => $entity['id']], $payload);

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
						'action' => 'delete',
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

			$this->logger->info('Successfully deleted ' . count($entities) . ' ' . $entityName . ' entities from Shopware, status code ' . $responseCode);
		} catch (ClientException $e) { // 4xx error
			$response = $e->getResponse();
			$this->logger->error('Shopware sync failed on SW, response: ' . $response->getBody()->getContents());
			throw new \Exception('Shopware sync failed on SW, message: ' . $e->getMessage() . ', code: ' . $e->getCode());
		} catch (ServerException $e) {
			$this->logger->error('Shopware sync failed on server, response: ' . $e->getMessage() . ', code: ' . $e->getCode());
			$this->logger->error('Request: ' . $e->getRequest()->getBody()->getContents());
			$this->logger->error('Response: ' . $e->getResponse()->getBody()->getContents());
		}
	}

	/**
	 * Generic delete method
	 * 
	 * @param string $name
	 * @param array<SearchFilter> $filters
	 */
	public function genericDelete(string $name, array $filters): int
	{
		try {
			$payload = [
				"entity" => $name,
				"action" => "delete",
				"criteria" => array_map(fn($filter) => $filter->serialize(), $filters)
			];

			$response = $this->httpClient->post('_action/sync', [
				'headers' => [
					'Authorization' => 'Bearer ' . $this->token,
				],
				"json" => ["deletion" => $payload]
			]);

			$responseCode = $response->getStatusCode();
			$response = json_decode($response->getBody()->getContents(), true);

			if ($responseCode > 299 || $responseCode < 200) {
				throw new \Exception('Failed to get entities from Shopware, response code: ' . $responseCode);
			}

			if (isset($response['deleted']) && isset($response['deleted'][$name])) {
				return count($response['deleted'][$name]);
			}

			return 0;
		} catch (\Exception $e) {
			$this->logger->error('Failed to delete entities from Shopware');
			$this->logger->error('Response: ' . $e->getResponse()->getBody()->getContents());
			throw new \Exception('Failed to delete entities from Shopware, message: ' . $e->getMessage() . ', code: ' . $e->getCode());
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
