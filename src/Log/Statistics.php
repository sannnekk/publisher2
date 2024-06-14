<?php declare(strict_types=1);

namespace HMnet\Publisher2\Log;

class Statistics {
	private array $successes = [];
	private array $errors = [];
	private bool $success = false;

	/**
	 * Set a value.
	 * 
	 * @param string $key
	 * @param mixed $value
	 */
	public function addSuccess(string $key): void {
		if (isset($this->successes[$key])) {
			$this->successes[$key] = $this->successes[$key]++;
			return;
		}

		$this->successes[$key] = 1;
	}

	/**
	 * Get the values.
	 * 
	 * @return array
	 */
	public function get(): array {
		return [
			'successes' => $this->successes,
			'errors' => $this->errors,
			'success' => $this->success
		];
	}
}