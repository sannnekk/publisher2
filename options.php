<?php

$CLI_OPTIONS = [
	'help' => [],
	'sync-products' => [
		'limit' => [
			'type' => 'number',
			'default' => 300,
			'description' => 'Anzahl der Produkte, die synchronisiert werden',
			'alias' => ['l']
		],
		'offset' => [
			'type' => 'number',
			'default' => 0,
			'description' => 'Offset der Produkte, die synchronisiert werden',
			'alias' => ['o']
		],
		'remove-orphans' => [
			'type' => 'number',
			'default' => false,
			'description' => 'Sollen auch Produkte gelöscht werden, die nicht mehr in der Quelle existieren?',
			'alias' => ['r']
		],
		'sort-x-out' => [
			'type' => 'boolean',
			'default' => false,
			'description' => 'Produkte mit X in Artikelnummer aussortieren',
			'alias' => ['x']
		],
		'with-images' => [
			'type' => 'boolean',
			'default' => false,
			'description' => 'Sollen auch die Bilder synchronisiert werden?',
			'alias' => ['i']
		],
		'with-categories' => [
			'type' => 'boolean',
			'default' => false,
			'description' => 'Sollen auch die Kategorien synchronisiert werden?',
			'alias' => ['c']
		],
		'with-prices' => [
			'type' => 'boolean',
			'default' => false,
			'description' => 'Sollen auch die erweiterten Preise synchronisiert werden?',
			'alias' => ['p']
		],
		'with-stock' => [
			'type' => 'boolean',
			'default' => false,
			'description' => 'Sollen auch die Verfügbarkeiten synchronisiert werden?',
			'alias' => ['s']
		],
	],
	'complete-orders' => [
		'limit' => [
			'default' => 300,
			'description' => 'Anzahl der Bestellungen, die abgeschlossen werden',
			'alias' => ['l']
		]
	],
];
