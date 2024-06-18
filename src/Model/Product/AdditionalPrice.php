<?php

declare(strict_types=1);

namespace HMnet\Publisher2\Model\Product;

use HMnet\Publisher2\Model\Model;
use HMnet\Publisher2\Model\Collection\ProductCollection;

class AdditionalPrice extends Model
{
	public string $productId;
	public string $ruleId;
	public int $quantityStart = 1;
	public Price $price;

	public function id(): string
	{
		return md5($this->productId . $this->ruleId);
	}

	public static function name(): string
	{
		return 'price';
	}

	/**
	 * @param string $productId
	 * @param Price $price
	 * @param string $customerGroup (values: 'firm' | 'big-firm')
	 */
	public function __construct(string $productId, Price $price, string $customerGroup = 'firm')
	{
		$this->productId = $productId;
		$this->price = $price;
		$this->ruleId = $this->getRuleId($customerGroup);
	}

	private function getRuleId(string $customerGroup): string
	{
		switch ($customerGroup) {
			case 'firm':
				return $_ENV['SW_FIRMA_RULE_ID'];
			case 'big-firm':
				return $_ENV['SW_BIG_FIRMA_RULE_ID'];
			default:
				throw new \Exception('Unknown customer group');
		}
	}

	public function serialize(): array
	{
		return [
			'id' => $this->id(),
			'productId' => $this->productId,
			'quantityStart' => $this->quantityStart,
			'ruleId' => $this->ruleId,
			'price' => [$this->price->serialize()],
		];
	}

	/**
	 * @param ProductCollection $products
	 * @param array<array<string>> $csv
	 * @return array<string, array<AdditionalPrice>>
	 */
	public static function fromCSV(ProductCollection $products, array $bigFirmCsv, array $firmCsv): array
	{
		$additionalPrices = [];

		foreach ($bigFirmCsv as $bigFirmRow) {
			$productNumber = Product::productNumberFromCSV($bigFirmRow);
			$product = $products[$productNumber];

			$firmRow = array_filter($firmCsv, fn ($r) => Product::productNumberFromCSV($r) === $productNumber);

			if (count($firmRow) === 0) {
				continue;
			}

			$firmRow = array_values($firmRow)[0];

			if (!$product) {
				continue;
			}

			$firmPrice = new Price($product->id(), (float)$firmRow['PREIS1'], 'net');
			$bigFirmPrice = new Price($product->id(), (float)$bigFirmRow['PREIS1'], 'net');

			$additionalPrices[$productNumber] = [
				new AdditionalPrice($product->id(), $firmPrice, 'firm'),
				new AdditionalPrice($product->id(), $bigFirmPrice, 'big-firm'),
			];
		}

		return $additionalPrices;
	}
}
