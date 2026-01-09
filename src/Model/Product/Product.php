<?php

declare(strict_types=1);

namespace HMnet\Publisher2\Model\Product;

use HMnet\Publisher2\Model\Model;
use HMnet\Publisher2\Model\Collection\ProductCollection;
use HMnet\Publisher2\Model\Product\ProductMedia;

class Product extends Model
{
	public readonly string $productNumber;
	public string $name;
	public float $weight = 0;
	public float $width = 0;
	public float $height = 0;
	public int $stock = 0;
	public int $restockTime = 1;
	public int $minPurchase = 1;
	public int $actualMinPurchase = 1;
	public int $purchaseSteps = 3;
	public string $ean = "";
	public ?\DateTime $releaseDate = null;
	public bool $active = true;
	public bool $isCloseout = true;
	public array $customSearchKeywords = [];

	// compound values

	/**
	 * @var Price[]
	 */
	private array $price = [];

	/**
	 * @var AdditionalPrice[]
	 */
	private array $prices = [];

	/**
	 * @var Visibility[]
	 */
	private array $visibilities = [];

	/**
	 * @var array<string, mixed>
	 */
	private array $customFields = [
		'custom_x_score_sort' => 0,
	];

	/**
	 * @var array<ProductMedia>
	 */
	private array $media = [];
	private string $coverId;

	// category ids

	/**
	 * @var string[]
	 */
	private array $categoryIds = [];

	// default ids
	private string $cmsPageId;
	private string $taxId;

	// settings
	private bool $dontSyncCategories = false;

	public function __construct(string $productNumber)
	{
		$this->productNumber = $productNumber;

		// defaults
		$this->cmsPageId = $_ENV['SW_PRODUCT_CMS_PAGE_ID'];
		$this->taxId = $_ENV['SW_TAX_ID'];
	}

	public static function name(): string
	{
		return 'product';
	}

	public function id(): string
	{
		return md5($this->productNumber);
	}

	public function setPrice(float $price): void
	{
		$this->price = [new Price($this->id(), $price, 'gross')];
	}

	public function addAdditionalPrice(AdditionalPrice $additionalPrice): void
	{
		$this->prices[] = $additionalPrice;
	}

	/**
	 * @param array<string> $categoryIds
	 */
	public function addCategoryIds(array $categoryIds): void
	{
		$this->categoryIds = array_map(fn($id) => ["id" => $id], $categoryIds);
	}

	public function dontSyncCategories(): void
	{
		$this->dontSyncCategories = true;
	}

	public function sortOut(): void
	{
		$this->customFields['custom_x_score_sort'] = 999;
	}

	public function addSearchKeyword(string $keyword): void
	{
		$this->customSearchKeywords[] = $keyword;
	}

	public function addMedia(ProductMedia $media): void
	{
		$this->media[] = $media;
		$this->coverId = $media->id();
	}

	public function setVisibility(): void
	{
		$this->visibilities = [new Visibility($this->id())];
	}

	public function isXProduct(): bool
	{
		return str_contains($this->productNumber, 'x');
	}

	public function serialize(): array
	{
		$serialized = [
			'id' => $this->id(),
			'productNumber' => $this->productNumber,
			'name' => $this->name,
			'weight' => $this->weight,
			'width' => $this->width,
			'height' => $this->height,
			'stock' => $this->stock,
			'restockTime' => $this->restockTime,
			'ean' => $this->ean,
			'releaseDate' => $this->releaseDate->format($_ENV['DATE_FORMAT']),
			'active' => $this->active,
			'isCloseout' => $this->isCloseout,
			'price' => array_map(fn($price) => $price->serialize(), $this->price),
			'prices' => array_map(fn($price) => $price->serialize(), $this->prices),
			'visibilities' => array_map(fn($visibility) => $visibility->serialize(), $this->visibilities),
			'customSearchKeywords' => $this->customSearchKeywords,
			'cmsPageId' => $this->cmsPageId,
			'taxId' => $this->taxId,
			'customFields' => $this->customFields,
			// !IMPORTANT: 
			// manufacturerNumber is the field that is used to tell minPurchase and purchaseSteps for companies
			// as Shopware doesnt allow to have different minPurchase and purchaseSteps for different customer groups.
			// An extension in Shopware called 'HMnetCartRecalculator' is needed to make this work.
			// The value of manufacturerNumber is a string that contains actual minPurchase and purchaseSteps separated by '|'.
			// purchaseSteps MUST be 1
			'manufacturerNumber' => $this->getManufacturerNumber(),
			'minPurchase' => $this->actualMinPurchase,
			'purchaseSteps' => 1,
		];

		if (!$this->dontSyncCategories) {
			$serialized['categories'] = $this->categoryIds;
		}

		if (!empty($this->coverId)) {
			$serialized['coverId'] = $this->coverId;
			$serialized['media'] = array_map(fn($media) => $media->serialize(), $this->media);
		}

		return $serialized;
	}

	/**
	 * Get manufacturer number
	 */
	private function getManufacturerNumber(): string
	{
		return $this->minPurchase . '|' . $this->purchaseSteps;
	}

	/**
	 * Create a Product from CSV row
	 * 
	 * @param array<string> $csv
	 * @param array<string> $priceRow
	 * @return Product
	 */
	public static function fromCSVRow(array $csv, array $priceRow): Product
	{
		$product = new Product(self::productNumberFromCSV($csv));
		$product->name = self::nameFromCSV($csv['BEZEICHNUNG1']);
		$product->weight = (float) $csv['GEWICHT_GR'];
		$product->width = (float) $csv['LAENGE_MM'];
		$product->height = (float) $csv['BREITE_MM'];
		$product->ean = $csv['EAN_NR'];
		$product->releaseDate = new \DateTime($csv['ERSCHEINUNG']);
		$product->minPurchase = (int) $csv['VERPACK_EINHEIT'];
		$product->purchaseSteps = (int) $csv['VERPACK_EINHEIT'];

		$product->setVisibility();
		$product->setPrice((float) $priceRow['WERT1']);

		// add search keyword for product number without prefix
		$product->addSearchKeyword(substr($product->productNumber, 2));

		return $product;
	}

	/**
	 * Get name from CSV row
	 * 
	 * @param string $name
	 * @return string
	 */
	private static function nameFromCSV(string $name): string
	{
		if (str_starts_with($name, 'PK')) {
			return 'Postkarte' . substr($name, 2);
		}

		if (str_starts_with($name, 'DK')) {
			return 'Klappkarte' . substr($name, 2);
		}

		return $name;
	}

	/**
	 * Create a ProductCollection from CSV and prices
	 * 
	 * @param array<array<string>> $csv
	 * @param array<array<string>> $prices
	 * @return ProductCollection
	 */
	public static function fromCSV(array $csv, array $prices): ProductCollection
	{
		$products = [];

		foreach ($csv as $row) {
			$productNumber = self::productNumberFromCSV($row);
			$priceRows = array_filter($prices, fn($priceRow) => $priceRow['TITEL_NR'] === $row['TITEL_NR']);

			if (count($priceRows) === 0) {
				continue;
			}

			$priceRow = array_values($priceRows)[0];

			$products[$productNumber] = Product::fromCSVRow($row, $priceRow);
		}

		return new ProductCollection($products);
	}

	/**
	 * Get product number from CSV row
	 * 
	 * @param array<string> $csv
	 * @return string
	 */
	public static function productNumberFromCSV($csv): string
	{
		return $_ENV['PRODUCT_NUMBER_PREFIX'] . $csv['TITEL_NR'];
	}
}
