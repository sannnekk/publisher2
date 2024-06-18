<?php

declare(strict_types=1);

namespace HMnet\Publisher2\Model\Category;

use HMnet\Publisher2\Model\Model;
use HMnet\Publisher2\Model\Product\Product;
use HMnet\Publisher2\Model\Collection\CategoryCollection;

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

		// defaults
		$this->salesChannelId = $_ENV['SW_SALES_CHANNEL_ID'];
		$this->cmsPageId = $_ENV['SW_CATEGORY_LAYOUT_ID'];
	}

	public function getChildren(): array
	{
		return array_values($this->children);
	}

	public function getLeafIds($level = 0): array
	{
		$leafIds = [];

		foreach ($this->children as $child) {
			if (empty($child->children)) {
				$leafIds[] = $child->id;
			} else {
				$leafIds = array_merge($leafIds, $child->getLeafIds($level + 1));
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
	 * @return CategoryCollection
	 */
	public static function fromCSV(array $csv): CategoryCollection
	{
		$trees = [];

		foreach ($csv as $row) {
			$productNumber = Product::productNumberFromCSV($row);
			$csvId = $row['WEB_GRP'];
			$path = explode('#', $row['WEB_GRP_T']);

			if (!isset($trees[$productNumber])) {
				$trees[$productNumber] = [];
			}

			self::createOrUpdateTree($trees[$productNumber], $csvId, $path);
		}

		return new CategoryCollection($trees);
	}

	/**
	 * Create or update the tree of categories
	 * 
	 * @param array<string, Category> $tree
	 * @param string $csvId
	 * @param array<string> $path
	 */
	private static function createOrUpdateTree(array &$tree, string $csvId, array $path): void
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
