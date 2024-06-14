<?php

declare(strict_types=1);

namespace HMnet\Publisher2\Controller;

use HMnet\Publisher2\Log\Statistics;
use HMnet\Publisher2\Model\Product\Product;
use HMnet\Publisher2\Model\Category\Category;
use HMnet\Publisher2\Services\Api\ShopwareService;
use HMnet\Publisher2\Services\Csv\CsvParsingService;
use HMnet\Publisher2\Model\Local\Stock;
use HMnet\Publisher2\Model\Local\Image;
use HMnet\Publisher2\Model\Product\AdditionalPrice;

class ProductSyncController extends Controller
{
	private readonly CsvParsingService $csvParsingService;
	private readonly ShopwareService $shopwareService;

	public function __construct(array $options)
	{
		parent::__construct($options);

		$this->csvParsingService = new CsvParsingService([
			'delimiter' => '|',
			'enclosure' => '"',
			'escape' => '\\',
		]);
		$this->shopwareService = new ShopwareService(
			$_ENV['SW_API_URL'],
			$_ENV['SW_ADMIN_USER'],
			$_ENV['SW_ADMIN_PASSWORD']
		);
	}

	public function handle(array $options): Statistics
	{
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
			$products->dontSyncXProductCategories();
		}

		// 4. Set stocks if option is set
		if ($this->options['with-stocks']) {
			$stocks = Stock::fromCSV($data['stocks']);
			$products->addStocks($stocks);
		}

		// 5. Set prices if option is set
		if ($this->options['with-prices']) {
			$prices = AdditionalPrice::fromCSV($products, $data['prices']);
			$products->addAdditionalPrices($prices);
		}

		// 6. Set categories if option is set
		if ($this->options['with-categories']) {
			$categories = Category::fromCSV($data['categories']);
			$products->addCategories($categories);

			$this->shopwareService->syncEntities($categories);
		}

		// 7. Sync products
		$this->shopwareService->syncEntities($products->toArray());

		// 8. Remove orphans if option is set
		if ($this->options['remove-orphans']) {
			$this->shopwareService->removeOrphants($products->toArray());
		}

		// 9. Upload images if option is set
		if ($this->options['with-images']) {
			$images = Image::fromFolder($_ENV['IMAGES_FOLDER']);
			$this->shopwareService->uploadImages($images);
		}

		return $statistics;
	}
}
