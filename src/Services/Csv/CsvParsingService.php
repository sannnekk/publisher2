<?php declare(strict_types=1);

namespace HMnet\Publisher2\Services\Csv;

class CsvParsingService {
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
	public function __construct(array $options = []) {
		$this->delimiter = $options['delimiter'] ?? ';';
		$this->enclosure = $options['enclosure'] ?? '"';
		$this->escape = $options['escape'] ?? '\\';
	}

	/**
	 * Parse a CSV file.
	 * 
	 * @param string $file
	 * @return array
	 */
	public function parse(string $file): array {
		$handle = fopen($file, 'r');
		$lines = [];

		while (($data = fgetcsv($handle, 0, $this->delimiter, $this->enclosure, $this->escape)) !== false) {
			$lines[] = $data;
		}

		fclose($handle);

		return $lines;
	}
}