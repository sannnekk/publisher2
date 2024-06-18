<?php

declare(strict_types=1);

namespace HMnet\Publisher2\Services\Csv;

class CsvParsingService
{
	private string $delimiter;
	private string $enclosure;
	private string $escape;

	/**
	 * CsvParsingService constructor.
	 * 
	 * @param array $options
	 *  - delimiter: string
	 *  - enclosure: string
	 *  - escape: string
	 */
	public function __construct(array $options = [])
	{
		$this->delimiter = $options['delimiter'] ?? ';';
		$this->enclosure = $options['enclosure'] ?? '"';
		$this->escape = $options['escape'] ?? '\\';
	}

	/**
	 * Parse a CSV file in an associative array
	 * 
	 * @param string $file
	 * @return array
	 */
	public function parse(string $file): array
	{
		$handle = fopen($file, 'r');
		$headers = fgetcsv($handle, 0, $this->delimiter, $this->enclosure, $this->escape);
		$data = [];

		while ($row = fgetcsv($handle, 0, $this->delimiter, $this->enclosure, $this->escape)) {
			if (count($headers) > count($row)) {
				$row = array_pad($row, count($headers), null);
			}

			// trim all values
			$row = array_map('trim', $row);

			// convert numeric values to float
			$row = array_map(function ($value) {
				return $this->convertToFloatIfNumeric($value) ?? $value;
			}, $row);

			$data[] = array_combine($headers, $row);
		}

		fclose($handle);

		return $data;
	}

	/**
	 * Convert a string to a float if it is numeric and contains a comma
	 * 
	 * @param string|null $str
	 * @return float|null
	 */
	private function convertToFloatIfNumeric(?string $str): ?float
	{
		if ($str === null) {
			return null;
		}

		if (preg_match("/^[0-9,]+$/", $str) && str_contains($str, ',')) {
			$str = str_replace(',', '.', $str);
			return floatval($str);
		}

		return null;
	}
}
