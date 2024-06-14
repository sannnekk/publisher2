<?php declare(strict_types=1);

namespace HMnet\Publisher2\Controller;

use HMnet\Publisher2\Log\Statistics;

abstract class Controller {
	protected array $options;
	
	public function __construct(array $options) {
		$this->options = $options;
	}

	abstract public function handle(array $options): Statistics;
}
