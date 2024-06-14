<?php

declare(strict_types=1);

namespace HMnet\Publisher2\Model\Category;

use HMnet\Publisher2\Model\Model;
use HMnet\Publisher2\Model\Product\Product;

class Category extends Model
{
	public string $id;
	public string $name;
	public string $parentId;
	public string $productAssignmentType = 'product';
	public string $type = 'page';
	public string $salesChannelId;
	public string $cmsPageId;
	public bool $displayNestedProducts = true;
	public bool $active = true;

	/**
	 * @var array<string, Category>
	 */
	private array $children = [];

	public static function id($csvId): string
	{
		return md5($csvId);
	}

	public static function name(): string
	{
		return 'category';
	}

	public function __construct(string $id, string $name, ?string $parentId)
	{
		$this->id = $id;
		$this->name = $name;
		$this->parentId = $parentId ?? $_ENV['SW_ROOT_CATEGORY_ID'];
	}

	public function getChildren(): array
	{
		return array_values($this->children);
	}

	public function getLeafIds(): array
	{
		$leafIds = [];

		foreach ($this->children as $child) {
			if (empty($child->children)) {
				$leafIds[] = $child->id;
			} else {
				$leafIds = array_merge($leafIds, $child->getLeafIds());
			}
		}

		return $leafIds;
	}

	public function serialize(): array
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'parentId' => $this->parentId,
			'productAssignmentType' => $this->productAssignmentType,
			'type' => $this->type,
			'salesChannelId' => $this->salesChannelId,
			'cmsPageId' => $this->cmsPageId,
			'displayNestedProducts' => $this->displayNestedProducts,
			'active' => $this->active,
		];
	}

	/**
	 * Create trees of categories for each product
	 * 
	 * @param array<array<string>> $csv
	 * @return array<string, array<string, Category>>
	 */
	public static function fromCSV(array $csv): array
	{
		$trees = [];

		foreach ($csv as $row) {
			$productNumber = Product::productNumberFromCSV($row);
			$csvId = $row['csvId'];
			$path = explode('#', $row['path']);

			if (!isset($trees[$productNumber])) {
				$trees[$productNumber] = [];
			}

			self::createOrUpdateTree($trees[$productNumber], $csvId, $path);
		}

		return $trees;
	}

	private static function createOrUpdateTree(Category &$tree, string $csvId, array $path)
	{
		$id = Category::id($csvId);
		$parentId = null;

		foreach ($path as $part) {
			if (!isset($tree[$part])) {
				$tree[$part] = new Category($id, $part, $parentId);
			}

			$parentId = $tree[$part]->id;
			$tree = &$tree[$part]->children;
		}
	}
}
