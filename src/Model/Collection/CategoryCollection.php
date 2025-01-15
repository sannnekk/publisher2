<?php

declare(strict_types=1);

namespace HMnet\Publisher2\Model\Collection;

use HMnet\Publisher2\Model\Collection\EntityCollection;
use HMnet\Publisher2\Model\Category\Category;

/**
 * @extends \ArrayObject<string, Product>
 */
class CategoryCollection extends EntityCollection
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
	 * Get the categories as normal php array
	 * 
	 * @return array<Category>
	 */
	public function toArray(): array
	{
		$categories = array_values(parent::getArrayCopy());

		// flatten the array
		$categories = array_reduce($categories, function ($acc, $subCategories) {
			return array_merge($acc, array_values($subCategories));
		}, []);

		// flatten trees
		$categories = $this->flattenTrees($categories);

		// filter out duplicates bt id
		$categories = array_reduce($categories, function ($acc, $category) {
			$acc[$category->id] = $category;
			return $acc;
		}, []);

		// reindex the array
		$categories = array_values($categories);

		return $categories;
	}

	/**
	 * Flatten the tree structure of categories
	 * 
	 * @param array<Category> $categories
	 * @return array<Category>
	 */
	private function flattenTrees($categories): array
	{
		$flattened = [];

		foreach ($categories as $category) {
			$flattened[] = $category;

			if (count($category->getChildren()) > 0) {
				$flattened = array_merge($flattened, $this->flattenTrees($category->getChildren()));
			}
		}

		return $flattened;
	}

	/**
	 * Get the root category ids
	 * 
	 * @return array<string>
	 */
	public function getRootCategories(): array
	{
		$categories = $this->toArray();
		$rootCategories = [];

		foreach ($categories as $category) {
			if ($category->parentId === $_ENV['SW_ROOT_CATEGORY_ID']) {
				$rootCategoryIds[] = $category;
			}
		}

		return $rootCategoryIds;
	}
}
