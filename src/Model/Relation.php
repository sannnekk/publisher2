<?php

declare(strict_types=1);

namespace Model;

abstract class Relation
{
	abstract public function serialize(): array;
	abstract static function name(): string|null;
}
