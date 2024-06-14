<?php

declare(strict_types=1);

namespace HMnet\Publisher2\Model\Product;

use HMnet\Publisher2\Model\Model;
use HMnet\Publisher2\Util\ProductCollection;

class AdditionalPrice extends Model
{
	public string $productId;
	public string $ruleId;
	public int $quantityStart = 1;
	public Price $price;

	public function id(): string
	{
		return md5($this->productId . $this->price->id());
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
			'price' => $this->price->serialize(),
		];
	}

	/**
	 * @param ProductCollection $products
	 * @param array<array<string>> $csv
	 * @return array<string, array<AdditionalPrice>>
	 */
	public static function fromCSV(ProductCollection $products, array $csv): array
	{
		$additionalPrices = [];

		foreach ($csv as $row) {
			$productNumber = Product::productNumberFromCSV($row);
			$product = $products[$productNumber];

			if (!$product) {
				continue;
			}

			$firmPrice = new Price($product->id, (float)$row['price']);
			$bigFirmPrice = new Price($product->id, (float)$row['bigFirmPrice']);

			$additionalPrices[$productNumber] = [
				new AdditionalPrice($product->id, $firmPrice, 'firm'),
				new AdditionalPrice($product->id, $bigFirmPrice, 'big-firm'),
			];
		}

		return $additionalPrices;
	}
}