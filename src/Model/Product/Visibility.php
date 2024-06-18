<?php

declare(strict_types=1);

namespace HMnet\Publisher2\Model\Product;

use HMnet\Publisher2\Model\Model;

class Visibility extends Model
{
	public string $productId;
	public string $salesChannelId;
	public int $visibility = 30;

	public function id(): string
	{
		return md5($this->productId);
	}

	public static function name(): string
	{
		return 'product-visibility';
	}

	public function __construct(string $productId)
	{
		$this->productId = $productId;

		// defaults
		$this->salesChannelId = $_ENV['SW_SALES_CHANNEL_ID'];
	}

	public function serialize(): array
	{
		return [
			'id' => $this->id(),
			'productId' => $this->productId,
			'salesChannelId' => $this->salesChannelId,
			'visibility' => $this->visibility,
		];
	}
}
