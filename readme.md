# SW Publisher

PHP command line tool to sync product data in csv with data in Shopware 6.
Works only from server! It means, the root and `data` folders should be available through the URL.

### Logs

For each start of the script.php there will be a new log file in `logs` folder.

### Requires:

- Composer
- Curl
- PHP 8.1 or higher
- Shopware 6.5 or newer
- linux (doesnt work on windows because of iconv)

### Settings

All the settings are in .env file:

#### Auth data

- `SW_ADMIN_USER` - Username of the shop admin
- `SW_ADMIN_PASSWORD` - Password of the shop admin
- `SW_API_URL` - URL of the API of the shop. Normally just <shop_url>/api

#### Shopware default ids

- `SW_SALES_CHANNEL_ID` - id of the sales channel where the products will be synced
- `SW_TAX_ID` - id of the tax that is used
- `SW_CURRENCY_ID` - id of the currency that is used
- `SW_ROOT_CATEGORY_ID` - id of the root category, the highest category in hierarchy
- `SW_CATEGORY_LAYOUT_ID` - id of the layout that is used for the categories
- `SW_PRODUCT_CMS_PAGE_ID` - id of the cms page that is used for the product detail page
- `SW_BIG_FIRMA_RULE_ID` - id of the rule that is used for the big firm prices
- `SW_FIRMA_RULE_ID` - id of the rule that is used for the firm prices
- `SW_LANGUAGE_DE_ID` - id of the german language
- `SW_LANGUAGE_EN_ID` - id of the english language
- `SW_ORDER_COMPLETE_STATE_ID` - id of the order state that is used for the completed orders

#### App settings

- `LOCATION` - url of the root folder of the app
- `DATE_FORMAT` - format of the date, should be "Y-m-d"
- `TAX_RATE` - tax rate in percent
- `PRODUCT_NUMBER_PREFIX` - prefix for the product numbers
- `REMOVE_ORPHANTS_LIMIT` - limit of the orphants that will be removed in one run
- `IMAGES_FOLDER` - folder where the images are stored
- `CSV_PRIVATE_PRICES_FILE` - path to the csv file with the private prices
- `CSV_BIG_FIRM_PRICES_FILE` - path to the csv file with the big firm prices
- `CSV_PRODUCTS_FILE` - path to the csv file with the products
- `CSV_CATEGORIES_FILE` - path to the csv file with the categories
- `CSV_STOCKS_FILE` - path to the csv file with the stocks
- `ALLOWED_IMAGE_EXTENSIONS` - allowed image extensions separated by |
- `MERKZETTEL_TO_HIDE_PRODUCT` - name of the merkzettel that will hide the product separated by |, example: `(MNr. 17)|(MNr. 12)`