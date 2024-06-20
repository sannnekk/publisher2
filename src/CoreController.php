<?php

declare(strict_types=1);

namespace HMnet\Publisher2;

use HMnet\Publisher2\Controller\ProductSyncController;
use HMnet\Publisher2\Controller\CompleteOrdersController;
use HMnet\Publisher2\Log\Logger;
use HMnet\Publisher2\Log\LoggerInterface;

class CoreController
{
	private readonly LoggerInterface $logger;

	public function __construct()
	{
		$this->logger = new Logger();
	}

	private array $controllers = [
		'sync-products' => ProductSyncController::class,
		'complete-orders' => CompleteOrdersController::class,
	];

	public function run(string $action, array $args): void
	{
		$controller = $this->controllers[$action];

		if (!class_exists($controller)) {
			throw new \Exception('Controller not found');
		}

		$this->logger->info('Running action: ' . $action);
		$this->logger->info('Args: ' . json_encode($args));

		$controller = new $controller($args);
		$controller->handle($args);
	}
}
