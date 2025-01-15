<?php

declare(strict_types=1);

namespace HMnet\Publisher2\Log;

use DateTime;
use DateTimeImmutable;

class Logger implements LoggerInterface
{
	private string $logFile;
	private string $debugFile;
	private int $nestingLevel;
	private static ?DateTimeImmutable $sriptStartTime = null;

	public function __construct(int $nestingLevel = 0)
	{
		if (self::$sriptStartTime === null) {
			self::$sriptStartTime = new DateTimeImmutable();
		}

		$this->logFile = 'logs/' . self::$sriptStartTime->format('Y-m-d_H_i_s') . '.log';
		$this->debugFile = 'logs/' . self::$sriptStartTime->format('Y-m-d_H_i_s') . '_debug.log';
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

	public function debug(string $title, mixed $obj): void
	{
		$content = $title . PHP_EOL . PHP_EOL . print_r($obj, true) . PHP_EOL;
		file_put_contents($this->debugFile, $content, FILE_APPEND);
	}

	private function message(string $level, string $message): string
	{
		return sprintf(
			'[%s] %s: %s %s' . PHP_EOL,
			(new DateTime())->format('Y-m-d H:i:s'),
			$level,
			str_repeat(' -', $this->nestingLevel),
			$message
		);
	}
}
