<?php

declare(strict_types=1);

namespace HMnet\Publisher2;

class CLI
{
	private array $availableActions;

	private string $action;

	private array $parsedArgs = [];

	public function __construct($options, $args)
	{
		$this->availableActions = $options;
		$this->parseAction($args);
		$this->parseArgs($args);
	}

	public function getAction(): string
	{
		return $this->action;
	}

	public function getArgs(): array
	{
		return $this->parsedArgs;
	}

	private function parseAction($args): void
	{
		$action = $args[1] ?? 'help';

		if (!array_key_exists($action, $this->availableActions) || $action === 'help') {
			$this->dieWithHelp();
		}

		$this->action = $action;
	}

	private function parseArgs($args): void
	{
		$options = $this->availableActions[$this->action] ?? [];

		foreach ($options as $optionName => $option) {
			$optionAliases = $option['alias'];
			$optionValue = $option['default'];
			$optionType = $option['type'];

			foreach ($args as $arg) {
				if (strpos($arg, '--' . $optionName) === 0) {
					$optionValue = $this->parseOptionValue('--' . $optionName, $optionType, $args);
				}

				foreach ($optionAliases as $alias) {
					if (strpos($arg, '-' . $alias) === 0) {
						$optionValue = $this->parseOptionValue('-' . $alias, $optionType, $args);
					}
				}
			}

			$this->parsedArgs[$optionName] = $optionValue;
		}
	}

	private function parseOptionValue(string $optionName, string $type, array $args): mixed
	{
		$optionIndex = array_search($optionName, $args);

		if ($optionIndex === false) {
			return null;
		}

		if ($type === 'boolean') {
			return true;
		}

		$value = $args[$optionIndex + 1] ?? null;

		if ($value === null) {
			return null;
		}

		if ($type === 'int') {
			return (int) $value;
		}

		return $value;
	}

	private function dieWithHelp(): void
	{
		echo "Usage: php script.php <action> [options]\n";
		echo "Available actions:\n\n";

		foreach ($this->availableActions as $action => $options) {
			$this->echoAction($action, $options);

			echo "\n";
		}

		echo "\n";

		die();
	}

	private function echoAction(string $action, array $options): void
	{
		echo "  $action\n";

		foreach ($options as $option => $config) {
			$default = $config['default'];
			$description = $config['description'];
			$alias = $config['alias'][0];

			echo "\t-$alias, --$option (default: $default): \t\t$description\n";
		}
	}
}
