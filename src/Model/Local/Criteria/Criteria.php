<?php

declare(strict_types=1);

namespace HMnet\Publisher2\Model\Local\Criteria;

class Criteria
{
	/**
	 * @var array<Filter>
	 */
	private array $filter;

	/**
	 * @var array<Sort>
	 */
	private array $sort;

	private int $page;

	private int $limit;

	private array $associations = [];

	private int $totalCountMode = 1;

	/**
	 * Default constructor
	 * 
	 * @param int $page
	 * @param int $limit
	 * @param array<Filter> $filter
	 * @param array<Sort> $sort
	 */
	public function __construct(int $page = 1, int $limit = 100, array $filter = [], array $sort = [])
	{
		$this->page = $page;
		$this->limit = $limit;
		$this->filter = $filter;
		$this->sort = $sort;
	}

	/**
	 * Add filter
	 * 
	 * @param Filter $filter
	 */
	public function addFilter(Filter $filter): void
	{
		$this->filter[] = $filter;
	}

	/**
	 * Add sort
	 * 
	 * @param Sort $sort
	 */
	public function addSort(Sort $sort): void
	{
		$this->sort[] = $sort;
	}

	public function serialize(): array
	{
		$serialized = [
			'page' => $this->page,
			'limit' => $this->limit,
			'total-count-mode' => $this->totalCountMode,
			'associations' => [],
			'filter' => [],
			'sort' => [],
		];

		if ($this->filter) {
			$serialized['filter'] = array_map(
				fn (Filter $filter) => $filter->serialize(),
				$this->filter
			);
		}

		if ($this->sort) {
			$serialized['sort'] = array_map(
				fn (Sort $sort) => $sort->serialize(),
				$this->sort
			);
		}

		if ($this->associations) {
			$serialized['associations'] = $this->associations;
		}

		return $serialized;
	}
}
