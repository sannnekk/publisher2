<?php

declare(strict_types=1);

namespace HMnet\Publisher2\Model\Collection;

abstract class EntityCollection extends \ArrayObject
{
	/**
	 * Default constructor
	 * 
	 * @param array<string, Product> $products
	 */
	public function __construct(array $products = [])
	{
		parent::__construct($products);
	}

	/**
	 * Get copy of the collection as array
	 */
	public abstract function toArray(): array;
}
