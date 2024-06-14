<?php declare(strict_types=1);

namespace HMnet\Publisher2;

use HMnet\Publisher2\Controller\ProductSyncController;
use HMnet\Publisher2\Controller\CompleteOrdersController;

class CoreController {
	private array $controllers = [
		'product-sync' => ProductSyncController::class,
		'complete-orders' => CompleteOrdersController::class,
	];

	public function run(string $action, array $args): void {
		$controller = $this->controllers[$action];

		if (!class_exists($controller)) {
			throw new \Exception('Controller not found');
		}

		$controller = new $controller($args);
		$pipe = $controller->getPipe($args);
		$pipe->run();
	}
}