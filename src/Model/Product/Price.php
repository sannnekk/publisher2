<?php

declare(strict_types=1);

namespace HMnet\Publisher2\Model\Product;

use HMnet\Publisher2\Model\Model;

class Price extends Model
{
	public string $productId;
	public string $currencyId;
	public float $gross;
	public float $net;
	public bool $linked = false;

	public static function name(): string|null
	{
		return null;
	}

	public function id(): string
	{
		return md5($this->productId . $this->currencyId);
	}

	/**
	 * Default constructor
	 * 
	 * @param string $productId
	 * @param float $price
	 * @param string $priceType (values: gross|net)
	 */
	public function __construct(string $productId, float $price, string $priceType = 'gross')
	{
		$this->productId = $productId;
		$this->setPrice($price, $priceType);

		// defaults
		$this->currencyId = $_ENV['SW_CURRENCY_ID'];
	}

	/**
	 * Set price
	 * 
	 * @param float $price
	 * @param string $priceType (values: gross|net)
	 */
	public function setPrice(float $price, string $priceType = 'gross'): void
	{
		$tax = 1 + ((float)$_ENV['TAX_RATE'] / 100);

		if ($priceType === 'gross') {
			$this->gross = $this->round($price);
			$this->net = $this->round($price / $tax);
		} else {
			$this->net = $this->round($price);
			$this->gross = $this->round($price * $tax);
		}
	}

	/**
	 * Round price to 2 decimal places
	 * 
	 * @param float $price
	 * @return float
	 */
	private function round(float $price): float
	{
		return round($price * 100) / 100;
	}

	public function serialize(): array
	{
		return [
			'id' => $this->id(),
			'productId' => $this->productId,
			'currencyId' => $this->currencyId,
			'gross' => $this->gross,
			'net' => $this->net,
			'linked' => $this->linked,
		];
	}
}
