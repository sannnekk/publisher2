<?php

declare(strict_types=1);

namespace HMnet\Publisher2\Model\Search;

class SearchFilter
{
	private SearchType $type;
	private string $field;
	private string|array $value;

	public function __construct(SearchType $type, string $field, string|array $value)
	{
		$this->type = $type;
		$this->field = $field;
		$this->value = $value;
	}

	public function serialize(): array
	{
		$serialized = [
			'type' => $this->type->value,
			'field' => $this->field,
			'value' => $this->value,
		];

		return $serialized;
	}
}
