<?php declare(strict_types=1);

namespace HMnet\Publisher2\Model;

abstract class Model {
	abstract function serialize(): array;
}