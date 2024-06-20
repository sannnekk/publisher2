<?php

declare(strict_types=1);

namespace HMnet\Publisher2\Controller;

use HMnet\Publisher2\Log\Statistics;
use HMnet\Publisher2\Services\Api\ShopwareService;
use HMnet\Publisher2\Log\LoggerInterface;
use HMnet\Publisher2\Log\Logger;
use HMnet\Publisher2\Model\Order\Order;
use HMnet\Publisher2\Model\Local\Criteria\Criteria;
use HMnet\Publisher2\Model\Local\Criteria\Filter;
use HMnet\Publisher2\Model\Local\Criteria\Sort;

class CompleteOrdersController extends Controller
{
	private readonly ShopwareService $shopwareService;
	private readonly LoggerInterface $logger;

	public function __construct()
	{
		$this->shopwareService = new ShopwareService(
			$_ENV['SW_API_URL'],
			$_ENV['SW_ADMIN_USER'],
			$_ENV['SW_ADMIN_PASSWORD']
		);

		$this->logger = new Logger();
	}

	public function handle(array $options): Statistics
	{
		$statistics = new Statistics();

		if (!isset($options['limit'])) {
			throw new \InvalidArgumentException('Limit is required');
		}

		// 1. Get all incomplete orders from last 24h from SW
		$this->logger->info('Getting incomplete orders from last 24h from SW');
		$criteria = new Criteria(1, $options['limit']);

		$dateFilter = new Filter('range', 'orderDateTime', null, [
			'gte' => (new \DateTime())->modify('-2 days'),
		]);
		$idFilter = new Filter('equalsAny', 'stateMachineState.id', $_ENV['SW_ORDER_COMPLETE_STATE_ID']);

		$criteria->addFilter($dateFilter);
		$criteria->addFilter($idFilter);

		$sort = new Sort('orderDateTime', 'ASC');

		$criteria->addSort($sort);

		/**
		 * @var array<Order>
		 */
		$orders = $this->shopwareService->getEntities(Order::class, $criteria);

		$this->logger->info('Found ' . count($orders) . ' incomplete orders from last 24h from SW');

		if (count($orders) > $options['limit']) {
			$this->logger->info('Limit reached, stopping');
			$this->logger->info('Limit: ' . ($options['limit']) . ', found: ' . count($orders));
			return $statistics;
		}

		// 2. update states
		$this->logger->info('Updating states of orders');
		foreach ($orders as $order) {
			$this->shopwareService->setOrderStatus($order->id, 'process');
			$this->shopwareService->setOrderStatus($order->id, 'complete');
		}

		$this->logger->info('States of orders updated');

		return $statistics;
	}
}
