<?php declare(strict_types=1);

namespace HMnet\Publisher2\Controller;

use HMnet\Publisher2\Log\Statistics;
use HMnet\Publisher2\Model\Product\Product;
use HMnet\Publisher2\Services\Api\ShopwareService;
use HMnet\Publisher2\Services\Csv\CsvParsingService;

class ProductSyncController extends Controller {
	private readonly CsvParsingService $csvParsingService;
	private readonly ShopwareService $shopwareService;

	public function __construct(array $options) {
		parent::__construct($options);

		$this->csvParsingService = new CsvParsingService([
			'delimiter' => ';',
			'enclosure' => '"',
			'escape' => '\\',
		]);
		$this->shopwareService = new ShopwareService($_ENV['SW_API_URL']);
	}

	public function handle(array $options): Statistics {
		$statistics = new Statistics();
		
		// 1. Parse CSV files
		$files = [
			'products' => $_ENV['CSV_PRODUCTS_FILE'],
			'categories' => $_ENV['CSV_CATEGORIES_FILE'],
			'prices' => $_ENV['CSV_PRICES_FILE'],
			'stocks' => $_ENV['CSV_STOCKS_FILE'],
		];

		$data = [];

		foreach ($files as $key => $file) {
			$data[$key] = $this->csvParsingService->parse($file);
		}

		// 2. Create entities from csv
		$products = Product::fromCSV($data['products']);

		// 3. Filter out products with 'x' in product number if option is set
		if ($this->options['sort-x-out']) {
			foreach ($products as &$product) {
				if ($product->isXProduct()) {
					$product->dontSyncCategories();
				}
			}
		}

		// 4. Set stocks if option is set
		if ($this->options['with-stocks']) {
			// TODO: Implement
		}

		// 5. Set prices if option is set
		if ($this->options['with-prices']) {
			// TODO: Implement
		}

		// 6. Set categories if option is set
		if ($this->options['with-categories']) {
			// TODO: Implement
		}

		// 7. Sync products
		foreach ($products as $product) {
			// TODO: Implement
		}

		// 8. Remove orphans if option is set
		if ($this->options['remove-orphans']) {
			// TODO: Implement
		}

		// 9. Upload images if option is set
		if ($this->options['with-images']) {
			// TODO: Implement
		}

		return $statistics;
	}
}