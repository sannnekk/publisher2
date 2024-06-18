<?php

declare(strict_types=1);

namespace HMnet\Publisher2\Model;

interface IConstructableFromSWResponse
{
	/**
	 * Construct object from Shopware API response
	 * 
	 * @param array<mixed> $response
	 */
	public static function fromSWResponse(array $response): self;
}
