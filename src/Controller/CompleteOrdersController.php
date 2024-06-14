<?php declare(strict_types=1);

namespace HMnet\Publisher2\Controller;

use HMnet\Publisher2\Log\Statistics;

class CompleteOrdersController extends Controller {
	public function handle(array $options): Statistics {
		$statistics = new Statistics();

		return $statistics;
	}
}