<?php

declare(strict_types=1);

namespace HMnet\Publisher2\Model\Local\Criteria;

class Sort
{
	private string $field;

	/**
	 * @var 'ASC'|'DESC'
	 */
	private string $order;

	private bool $naturalSorting = false;

	public function __construct(string $field, string $order = 'DESC')
	{
		$this->field = $field;
		$this->order = $order;
	}

	public function serialize()
	{
		return [
			'field' => $this->field,
			'order' => $this->order,
			'naturalSorting' => $this->naturalSorting,
		];
	}
}
