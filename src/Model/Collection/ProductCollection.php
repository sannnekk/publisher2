<?php

declare(strict_types=1);

namespace HMnet\Publisher2\Model\Collection;

use HMnet\Publisher2\Log\Logger;
use HMnet\Publisher2\Log\LoggerInterface;
use HMnet\Publisher2\Model\Collection\EntityCollection;
use HMnet\Publisher2\Model\Product\Product;
use HMnet\Publisher2\Model\Local\Stock;

/**
 * @extends \ArrayObject<string, Product>
 */
class ProductCollection extends EntityCollection
{
	private readonly LoggerInterface $logger;

	/**
	 * Default constructor
	 * 
	 * @param array<string, Product> $products
	 */
	public function __construct(array $products = [])
	{
		parent::__construct($products);

		$this->logger = new Logger(1);
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
	 * If executed, the products containing 'x' in product number
	 * will be sorted to the end
	 */
	public function sortXProductsOut()
	{
		$counter = 0;

		foreach ($this as $product) {
			if ($product->isXProduct()) {
				$product->sortOut();
				$counter++;
			}
		}

		$this->logger->info("Sorted out $counter x products");
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
		$counter = 0;

		foreach ($this as $productNumber => &$product) {
			[$firmAdditionalPrice] = $additionalPrices[$productNumber] ?? [null, null];

			if ($firmAdditionalPrice) {
				$product->addAdditionalPrice($firmAdditionalPrice);
				$counter++;
			}
		}

		$this->logger->info("Added $counter additional prices to the products");
	}

	/**
	 * Remove synced categories from the products
	 */
	public function removeSyncedCategories(array $syncedCategories): void
	{
		$counter = 0;

		foreach ($this as &$product) {
			$product->removeSyncedCategories($syncedCategories);
			$counter++;
		}

		$this->logger->info("Removed synced categories from $counter products");
	}

	/**
	 * Add categories to the products
	 * 
	 * @param array<string, array<string, Category>> $categories
	 */
	public function addCategories(CategoryCollection $categories): void
	{
		$counter = 0;
		$productsWithNoCategories = [];

		foreach ($this as $productNumber => &$product) {
			$productCategories = $categories[$productNumber] ?? [];

			$leafCategoryIds = array_map(fn ($category) => $category->getLeafIds(), $productCategories);

			// Flatten the array
			$leafCategoryIds = array_reduce($leafCategoryIds, 'array_merge', []);

			if (count($productCategories) < 1) {
				$productsWithNoCategories[] = $productNumber;
			}

			$product->addCategoryIds($leafCategoryIds);
			$counter++;
		}

		$this->logger->info("Added to $counter products the categories");
		$this->logger->info("Products with no categories: " . implode(", ", $productsWithNoCategories));
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
