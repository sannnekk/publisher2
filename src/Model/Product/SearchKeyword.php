<?php

declare(strict_types=1);

namespace HMnet\Publisher2\Model\Product;

use HMnet\Publisher2\Model\Model;

class SearchKeyword extends Model
{
	public string $productId;
	public string $languageId;
	public string $keyword;
	public int $ranking = 0;

	public function id(): string
	{
		return md5($this->productId . $this->keyword);
	}

	public static function name(): string
	{
		return 'search-keyword';
	}

	/**
	 * Create a new search keyword
	 * 
	 * @param string $productId
	 * @param string $keyword
	 * @param string $language [values: 'de', 'en']
	 */
	public function __construct(string $productId, string $keyword, string $language = 'de')
	{
		$this->productId = $productId;
		$this->keyword = $keyword;
		$this->languageId = $this->getLanguageId($language);
	}

	public function serialize(): array
	{
		return [
			'id' => $this->id(),
			'productId' => $this->productId,
			'languageId' => $this->languageId,
			'keyword' => $this->keyword,
			'ranking' => $this->ranking,
		];
	}

	private function getLanguageId(string $language): string
	{
		switch ($language) {
			case 'de':
				return $_ENV['SW_LANGUAGE_DE'];
			case 'en':
				return $_ENV['SW_LANGUAGE_EN'];
			default:
				throw new \Exception('Unknown language: ' . $language);
		}
	}
}
