<?php declare(strict_types=1);

namespace HMnet\Publisher2\Model\Product;

use HMnet\Publisher2\Model\Model;

class Product extends Model {
	public readonly string $productNumber;
	public string $name;
	public string $publisher;
	public int $weight;
	public int $width;
	public int $height;
	public int $stock;
	public int $restockTime = 1;
	public int $ean;
	public int $minPurchase;
	public int $purchaseSteps;
	public \DateTime $releaseDate;
	public bool $active = true;
	public bool $isCloseout = true;

	// compound values
	private array $price = [];
	private array $visibilities = [];
	private array $searchKeywords = [];

	// category ids
	private array $categories = [];

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

	public function id(): string {
		return md5($this->productNumber);
	}

	public function setPrice(int $price): void {
		$this->price = [new Price($this->id(), $price)];
	}

	public function addCategory(string $categoryId): void {
		$this->categories[] = $categoryId;
	}

	public function dontSyncCategories(): void {
		$this->dontSyncCategories = true;
	}

	public function addSearchKeyword(string $keyword): void {
		$this->searchKeywords[] = new SearchKeyword($this->id(), $keyword, 'de');
		$this->searchKeywords[] = new SearchKeyword($this->id(), $keyword, 'en');
	}

	public function setVisibility(): void {
		$this->visibilities = [new Visibility($this->id())];
	}

	public function isXProduct(): bool {
		return strpos($this->productNumber, 'x') !== false;
	}

	public function serialize(): array {
		$serialized = [
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
			'visibilities' => array_map(fn($visibility) => $visibility->serialize(), $this->visibilities),
			'searchKeywords' => array_map(fn($keyword) => $keyword->serialize(), $this->searchKeywords),
			'cmsPageId' => $this->cmsPageId,
			'taxId' => $this->taxId,
			// !IMPORTANT: 
			// this is the field that is used to tell minPurchase and purchaseSteps for companies
			// as Shopware doesnt allow to have different minPurchase and purchaseSteps for different customer groups.
			// An extension in Shopware called 'HMnetCartRecalculator' is needed to make this work.
			'manufacturerNumber' => $this->getManufacturerNumber(),
			'minPurchase' => 1,
			'purchaseSteps' => 1,
		];

		if (!$this->dontSyncCategories) {
			$serialized['categories'] = $this->categories;
		}

		return $serialized;
	}

	private function getManufacturerNumber(): string {
		return $this->minPurchase . '|' . $this->purchaseSteps;
	}

	public static function fromCSVRow(array $csv): Product {
		$product = new Product($_ENV['PRODUCT_NUMBER_PREFIX'] . $csv['TITEL_NR']);
		$product->productNumber = $csv['TITEL_NR'];
		$product->name = $csv['BEZEICHNUNG1'];
		$product->publisher = $csv['VERLAG'];
		$product->weight = $csv['GEWICHT_GR'];
		$product->width = $csv['LAENGE_MM'];
		$product->height = $csv['BREITE_MM'];
		$product->ean = $csv['EAN_NR'];
		$product->releaseDate = new \DateTime($csv['ERSCHEINUNG']);

		$product->setVisibility();

		return $product;
	}

	public static function fromCSV(array $csv): array {
		return array_map(fn($row) => self::fromCSVRow($row), $csv);
	}
}