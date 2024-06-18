<?php

declare(strict_types=1);

namespace HMnet\Publisher2\Model\Product;

use HMnet\Publisher2\Model\Model;
use HMnet\Publisher2\Model\Product\Product;

class ProductMedia extends Model
{
	private string $productNumber;
	private string $path;
	private string $productId;

	public static function name(): string
	{
		return 'product_media';
	}

	public function id()
	{
		return md5($this->productNumber);
	}

	public function getPath(): string
	{
		return $this->path;
	}

	public function url(): string
	{
		return 'https://' . $_ENV['LOCATION'] . '/' . $this->path;
	}

	public function extension(): string
	{
		return pathinfo($this->path, PATHINFO_EXTENSION);
	}

	public function __construct(string $productNumber, string $path)
	{
		$this->productNumber = $productNumber;
		$this->productId = (new Product($productNumber))->id();
		$this->path = $path;
	}

	/**
	 * Get Media items from folder
	 * Assumes that the folder contains only images
	 * Assumes that the filenames are productNumbers
	 * 
	 * @param string $folder
	 * @return array<string, ProductMedia>
	 */
	public static function fromFolder(string $folder): array
	{
		$images = [];

		$files = scandir($folder);

		foreach ($files as $file) {
			$path = $folder . '/' . $file;

			if (is_file($path) && self::extensionSupported($path)) {
				$productNumber = pathinfo($path, PATHINFO_FILENAME);
				$images[$productNumber] = new ProductMedia($productNumber, $path);
			}
		}

		return $images;
	}

	/**
	 * Check if the extension of the file is supported
	 * 
	 * @param string $path
	 * @return bool
	 */
	private static function extensionSupported(string $path): bool
	{
		$extension = pathinfo($path, PATHINFO_EXTENSION);
		$allowedExtensions = explode('|', $_ENV['ALLOWED_IMAGE_EXTENSIONS']);

		return in_array($extension, $allowedExtensions);
	}

	public function serialize(): array
	{
		return [
			'id' => $this->id(),
			'productId' => $this->productId,
			'mediaId' => $this->id(),
			'media' => [
				'id' => $this->id()
			]
		];
	}
}
