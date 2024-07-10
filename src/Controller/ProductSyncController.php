<?php

declare(strict_types=1);

namespace HMnet\Publisher2\Controller;

use HMnet\Publisher2\Log\LoggerInterface;
use HMnet\Publisher2\Log\Statistics;
use HMnet\Publisher2\Model\Product\Product;
use HMnet\Publisher2\Model\Category\Category;
use HMnet\Publisher2\Services\Api\ShopwareService;
use HMnet\Publisher2\Services\Csv\CsvParsingService;
use HMnet\Publisher2\Model\Local\Stock;
use HMnet\Publisher2\Model\Product\ProductMedia;
use HMnet\Publisher2\Model\Product\AdditionalPrice;
use HMnet\Publisher2\Log\Logger;

class ProductSyncController extends Controller
{
	private readonly CsvParsingService $csvParsingService;
	private readonly ShopwareService $shopwareService;
	private readonly LoggerInterface $logger;

	public function __construct(array $options)
	{
		parent::__construct($options);

		$this->logger = new Logger();

		$this->csvParsingService = new CsvParsingService([
			'delimiter' => '|',
			'enclosure' => '"',
			'escape' => '\\',
		]);

		$this->shopwareService = new ShopwareService(
			$_ENV['SW_API_URL'],
			$_ENV['SW_ADMIN_USER'],
			$_ENV['SW_ADMIN_PASSWORD'],
			intval($_ENV['DEBUG_MODE'])
		);
	}

	public function handle(array $options): Statistics
	{
		$statistics = new Statistics();

		// 1. Parse CSV files
		$files = [
			'products' => $_ENV['CSV_PRODUCTS_FILE'],
			'categories' => $_ENV['CSV_CATEGORIES_FILE'],
			'private-prices' => $_ENV['CSV_PRIVATE_PRICES_FILE'],
			'big-firm-prices' => $_ENV['CSV_BIG_FIRM_PRICES_FILE'],
			'stocks' => $_ENV['CSV_STOCKS_FILE'],
		];

		$data = [];

		foreach ($files as $key => $file) {
			$this->logger->info("Parsing file: $file");
			$data[$key] = $this->csvParsingService->parse($file);
		}

		// 2. Create entities from csv
		$this->logger->info("Creating entities from CSV files");
		$products = Product::fromCSV($data['products'], $data['private-prices']);

		// 3. Filter out products with 'x' in product number if option is set
		if ($this->options['sort-x-out']) {
			$this->logger->info("Filtering out products with 'x' in product number");
			$products->sortXProductsOut();
		}

		// 4. Set stocks if option is set
		if ($this->options['with-stock']) {
			$this->logger->info("Setting stocks");
			$stocks = Stock::fromCSV($data['stocks']);
			$products->addStocks($stocks);
		}

		// 5. Set prices if option is set
		if ($this->options['with-prices']) {
			$this->logger->info("Setting additional prices");
			$prices = AdditionalPrice::fromCSV($products, $data['big-firm-prices'], $data['products']);
			$products->addAdditionalPrices($prices);
		}

		// 6. Set categories if option is set
		if ($this->options['with-categories']) {
			$this->logger->info("Setting categories");
			$categories = Category::fromCSV($data['categories']);
			$products->addCategories($categories);

			$this->logger->info("Syncing categories with Shopware");
			$this->shopwareService->syncEntities($categories->toArray());
		}

		// 7. Prepare images if option is set
		if ($this->options['with-images']) {
			$this->logger->info("Parsing images");
			$images = ProductMedia::fromFolder($_ENV['IMAGES_FOLDER']);
			$products->addImages($images);

			// upload the images after the products are synced
		}

		// 8. Sync products
		$this->logger->info("Syncing products with Shopware");
		$this->shopwareService->syncEntities($products->toArray());

		if ($this->options['with-images']) {
			$this->logger->info("Uploading images to Shopware");
			$this->shopwareService->uploadImages($images);
		}

		// 10. Remove orphans if option is set
		if ($this->options['remove-orphans']) {
			$this->logger->info("Removing orphans");
			$this->shopwareService->removeOrphants($products->toArray());
		}

		return $statistics;
	}
}
