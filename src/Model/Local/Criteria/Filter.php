<?php

declare(strict_types=1);

namespace HMnet\Publisher2\Model\Local\Criteria;

class Filter
{
	private string $type;
	private string $field;
	private ?string $value;
	private ?array $parameters;

	public function __construct(string $type, string $field, ?string $value = null, ?array $parameters = null)
	{
		$this->type = $type;
		$this->field = $field;
		$this->value = $value;
		$this->parameters = $parameters;
	}

	public function addParameter(string $key, mixed $value): void
	{
		if (!$this->parameters) {
			$this->parameters = [];
		}

		if ($value instanceof \DateTime) {
			$value = $value->format($_ENV['DATE_FORMAT']) . "T22:00:00.000Z";
		}

		$this->parameters[$key] = $value;
	}

	public function serialize()
	{
		$serialized = [
			'type' => $this->type,
			'field' => $this->field,
		];

		if ($this->parameters) {
			$serialized['parameters'] = $this->parameters;
		}

		if ($this->value) {
			$serialized['value'] = $this->value;
		}

		return $serialized;
	}
}
