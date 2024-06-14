<?php declare(strict_types=1);

namespace HMnet\Publisher2\Services\Api;

class ShopwareService {
	private string $apiUrl;
	private string $apiKey;
	private array $authData = [

	];

	public function __construct(string $apiUrl) {
	}

	private function auth(): void {
	}

	public function createOrUpdateEntity(object $entity): void {
	}
}