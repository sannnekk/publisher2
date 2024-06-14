<?php

declare(strict_types=1);

namespace HMnet\Publisher2\Model\Product;

use HMnet\Publisher2\Model\Model;

class Price extends Model
{
	public string $productId;
	public string $currencyId;
	public int $gross;
	public int $net;
	public bool $linked = false;

	public static function name(): string|null
	{
		return null;
	}

	public function id(): string
	{
		return md5($this->productId . $this->currencyId);
	}

	public function __construct(string $productId, float $grossPrice)
	{
		$this->productId = $productId;
		$this->gross = $this->round($grossPrice ?? 0);
		$this->net = $this->round($grossPrice / (1 + $_ENV['SW_TAX'] / 100));

		// defaults
		$this->currencyId = $_ENV['SW_CURRENCY_ID'];
	}

	private function round(float $price): int
	{
		return ((int) round($price * 100)) / 100;
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
