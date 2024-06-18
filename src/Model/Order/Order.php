<?php

declare(strict_types=1);

namespace HMnet\Publisher2\Model\Order;

use HMnet\Publisher2\Model\IConstructableFromSWResponse;
use HMnet\Publisher2\Model\Model;

class Order extends Model implements IConstructableFromSWResponse
{
	public string $id;
	public string $orderNumber;

	public function __construct(string $orderNumber)
	{
		$this->orderNumber = $orderNumber;
	}

	public static function name(): string
	{
		return 'order';
	}

	public function serialize(): array
	{
		return [
			'id' => $this->id,
			'orderNumber' => $this->orderNumber,
		];
	}

	public static function fromSWResponse(array $response): self
	{
		$order = new self($response['orderNumber']);

		$order->id = $response['id'];

		return $order;
	}
}
