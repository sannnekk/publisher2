<?php

declare(strict_types=1);

namespace HMnet\Publisher2\Model\Local\Criteria;

class Filter
{
	private string $type;
	private ?string $field;
	private ?string $value;
	private ?array $parameters;
	private ?string $operator;

	/**
	 * @var array<Filter>
	 */
	private ?array $queries;

	public function __construct(string $type, ?string $field, ?string $value = null, ?array $parameters = null, ?string $operator = null, ?array $queries = null)
	{
		$this->type = $type;
		$this->field = $field;
		$this->value = $value;
		$this->operator = $operator;
		$this->parameters = $parameters;
		$this->queries = $queries;
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
		];

		if ($this->field) {
			$serialized['field'] = $this->field;
		}

		if ($this->parameters) {
			$serialized['parameters'] = $this->serializedParameters();
		}

		if ($this->value) {
			$serialized['value'] = $this->value;
		}

		if ($this->operator) {
			$serialized['operator'] = $this->operator;
		}

		if ($this->queries) {
			$serialized['queries'] = array_map(fn($query) => $query->serialize(), $this->queries);
		}

		return $serialized;
	}

	private function serializedParameters(): array
	{
		$serialized = [];

		foreach ($this->parameters as $key => $value) {
			if ($value instanceof \DateTime || $value instanceof \DateTimeImmutable) {
				$value = $value->format($_ENV['DATE_FORMAT']) . "T22:00:00.000Z";
			}

			$serialized[$key] = $value;
		}

		return $serialized;
	}
}
