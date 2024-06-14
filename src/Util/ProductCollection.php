<?php declare(strict_types=1);

namespace HMnet\Publisher2\Util;

use HMnet\Publisher2\Model\Product\Product;

/**
 * @extends \ArrayObject<string, Product>
 */
class ProductCollection extends \ArrayObject {
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
	public function dontSyncXProductCategories() {
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
	public function addStocks(array $stocks): void {
		foreach ($this as $productNumber => &$product) {
			$stock = $stocks[$productNumber] ?? null;

			if (!$stock) {
				$product->stock = 0;
				$product->active = true;
				continue;
			}

			$product->stock = $stock->setStockFromMerkzettel();
			$product->active = $stock->getIsActiveFromMerkzettel();
		}
	}

	/**
	 * Add additional prices to the products
	 * 
	 * @param array<string, AdditionalPrice>> $additionalPrices
	 */
	public function addAdditionalPrices(array $additionalPrices): void {
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
	public function addCategories(array $categories): void {
		foreach ($this as $productNumber => &$product) {
			$categories = $categories[$productNumber] ?? [];
			$leafCategoryIds = array_map(fn($category) => $category->getLeafCategoryIds(), $categories);
			
			$product->addCategoryIds($leafCategoryIds);
		}
	}

	/**
	 * Get the products as normal php array
	 * 
	 * @return array<Product>
	 */
	public function toArray(): array {
		return array_values($this->getArrayCopy());
	}
}