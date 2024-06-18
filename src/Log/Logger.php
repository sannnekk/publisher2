<?php

declare(strict_types=1);

namespace HMnet\Publisher2\Log;

use DateTime;

class Logger implements LoggerInterface
{
	private string $logFile;
	private int $nestingLevel;

	public function __construct(int $nestingLevel = 0)
	{
		$this->logFile = 'logs/' . (new DateTime())->format('Y-m-d_H_i_s') . '.log';
		$this->nestingLevel = $nestingLevel;

		if (!file_exists('logs')) {
			mkdir('logs');
		}
	}

	public function info(string $message): void
	{
		file_put_contents($this->logFile, $this->message('info', $message), FILE_APPEND);
	}

	public function error(string $message): void
	{
		file_put_contents($this->logFile, $this->message('error', $message), FILE_APPEND);
	}

	public function warning(string $message): void
	{
		file_put_contents($this->logFile, $this->message('warning', $message), FILE_APPEND);
	}

	private function message(string $level, string $message): string
	{
		return sprintf(
			'[%s] %s: %s' . str_repeat(" -", $this->nestingLevel) . PHP_EOL,
			(new DateTime())->format('Y-m-d H:i:s'),
			$level,
			$message
		);
	}
}
