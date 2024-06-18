<?php

declare(strict_types=1);

namespace HMnet\Publisher2\Model\Collection;

use HMnet\Publisher2\Model\Collection\EntityCollection;
use HMnet\Publisher2\Model\Product\Product;
use HMnet\Publisher2\Model\Local\Stock;

/**
 * @extends \ArrayObject<string, Product>
 */
class ProductCollection extends EntityCollection
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
	 * If executed, the products containing 'x' in product number
	 * will be synced, but the categories of them wont be synced
	 * 
	 */
	public function dontSyncXProductCategories()
	{
		foreach ($this as &$product) {
			if ($product->isXProduct()) {
				$product->dontSyncCategories();
			}
		}
	}

	/**
	 * Add stocks to the products
	 * 
	 * @param array<string, Stock> $stocks
	 */
	public function addStocks(array $stocks): void
	{
		foreach ($this as $productNumber => &$product) {
			$stock = $stocks[$productNumber] ?? null;

			if (!$stock) {
				$product->stock = 0;
				$product->active = true;
				continue;
			}

			$product->stock = $stock->getStockFromMerkzettel();
			$product->active = $stock->getIsActiveFromMerkzettel();
		}
	}

	/**
	 * Add additional prices to the products
	 * 
	 * @param array<string, AdditionalPrice>> $additionalPrices
	 */
	public function addAdditionalPrices(array $additionalPrices): void
	{
		foreach ($this as $productNumber => &$product) {
			[$firmAdditionalPrice, $bigFirmAdditionalPrice] = $additionalPrices[$productNumber] ?? [null, null];

			if ($firmAdditionalPrice) {
				$product->addAdditionalPrice($firmAdditionalPrice);
			}

			if ($bigFirmAdditionalPrice) {
				$product->addAdditionalPrice($bigFirmAdditionalPrice);
			}
		}
	}

	/**
	 * Add categories to the products
	 * 
	 * @param array<string, array<string, Category>> $categories
	 */
	public function addCategories(CategoryCollection $categories): void
	{
		foreach ($this as $productNumber => &$product) {
			$categories = $categories[$productNumber] ?? [];

			$leafCategoryIds = array_map(fn ($category) => $category->getLeafIds(), $categories);

			// Flatten the array
			$leafCategoryIds = array_reduce($leafCategoryIds, 'array_merge', []);

			$product->addCategoryIds($leafCategoryIds);
		}
	}

	/**
	 * Add images to the products
	 * 
	 * @param array<string, ProductMedia> $images
	 */
	public function addImages(array $images): void
	{
		foreach ($this as $productNumber => &$product) {
			$image = $images[$productNumber] ?? null;

			if (!$image) {
				continue;
			}

			$product->addMedia($image);
		}
	}

	/**
	 * Get the products as normal php array
	 * 
	 * @return array<Product>
	 */
	public function toArray(): array
	{
		return array_values(parent::getArrayCopy());
	}
}