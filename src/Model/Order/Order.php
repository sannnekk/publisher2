<?php

declare(strict_types=1);

namespace HMnet\Publisher2\Model\Order;

use HMnet\Publisher2\Model\Model;

class Order extends Model
{
	// TODO: implement Order model

	public static function name(): string
	{
		return 'order';
	}

	public function serialize(): array
	{
		return [];
	}
}
