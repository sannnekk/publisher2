<?php

declare(strict_types=1);

namespace HMnet\Publisher2\Log;

interface LoggerInterface
{
	public function info(string $message): void;
	public function error(string $message): void;
	public function warning(string $message): void;
}
