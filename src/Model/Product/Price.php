<?php declare(strict_types=1);

namespace HMnet\Publisher2\Model\Product;

use HMnet\Publisher2\Model\Model;

class Price extends Model {
	public string $productId;
	public string $currencyId;
	public int $gross;
	public int $net;
	public bool $linked = false;

	public function id(): string {
		return md5($this->productId . $this->currencyId);
	}

	public function __construct(string $productId, int $grossPrice) {
		$this->productId = $productId;
		$this->gross = $grossPrice ?? 0;
		$this->net = $grossPrice / (1 + $_ENV['SW_TAX'] / 100);

		// defaults
		$this->currencyId = $_ENV['SW_CURRENCY_ID'];
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