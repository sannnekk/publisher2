<?php

declare(strict_types=1);

namespace HMnet\Publisher2\Model\Local;

class Image
{
	private string $path;

	public function __construct(string $path)
	{
		$this->path = $path;
	}

	public static function fromFolder(string $folder): array
	{
		$images = [];

		$files = scandir($folder);

		foreach ($files as $file) {
			if (is_file($folder . '/' . $file)) {
				$images[] = new Image($folder . '/' . $file);
			}
		}

		return $images;
	}
}
