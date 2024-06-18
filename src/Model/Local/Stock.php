<?php

declare(strict_types=1);

namespace HMnet\Publisher2\Model\Local;

use HMnet\Publisher2\Model\Product\Product;

class Stock
{
	public string $productNumber;
	public int $stock;
	public ?string $merkzettel;

	public function __construct(string $productNumber, int $stock, ?string $merkzettel)
	{
		$this->productNumber = $productNumber;
		$this->stock = $stock;
		$this->merkzettel = $merkzettel;
	}

	public function getIsActiveFromMerkzettel(): bool
	{
		$merkzettelToHide = explode('|', $_ENV['MERKZETTEL_TO_HIDE_PRODUCT']);

		foreach ($merkzettelToHide as $merkzettel) {
			if (str_contains($this->merkzettel, $merkzettel)) {
				return false;
			}
		}

		return true;
	}

	public function getStockFromMerkzettel(): int
	{
		return $this->stock;
	}

	/**
	 * @param array<array<string>> $data
	 * @return array<string, Stock>
	 */
	public static function fromCSV(array $data): array
	{
		$stocks = [];

		foreach ($data as $row) {
			$productNumber = Product::productNumberFromCSV($row);
			$stock = (int) $row['BESTAND_LAG'];
			$merkzettel = $row['MELDE_NR_TEXT'] ?? null;

			$stocks[$productNumber] = new Stock($productNumber, $stock, $merkzettel);
		}

		return $stocks;
	}
}
