<?php declare(strict_types=1);

namespace HMnet\Publisher2\Model\Local;

use HMnet\Publisher2\Model\Product\Product;

class Stock {
	public string $productNumber;
	public int $stock;
	public ?string $merkzettel;

	public function __construct(string $productNumber, int $stock, ?string $merkzettel) {
		$this->productNumber = $productNumber;
		$this->stock = $stock;
		$this->merkzettel = $merkzettel;
	}

	public function getIsActiveFromMerkzettel(): bool {
		throw new \Exception('Not implemented');
	}

	public function setStockFromMerkzettel(): void {
		throw new \Exception('Not implemented');
	}

	/**
	 * @param array<array<string>> $data
	 * @return array<string, Stock>
	 */
	public static function fromCSV(array $data): array {
		$stocks = [];

		foreach ($data as $row) {
			$productNumber = Product::productNumberFromCSV($row);
			$stock = (int) $row['stock'];
			$merkzettel = $row['merkzettel'] ?? null;

			$stocks[$productNumber] = new Stock($productNumber, $stock, $merkzettel);
		}

		return $stocks;
	}
}