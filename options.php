<?php

$CLI_OPTIONS = [
	'help' => [],
	'sync-products' => [
		'remove-orphans' => [
			'type' => 'boolean',
			'default' => false,
			'description' => 'Von FTP gelöschte Produkte auch im Shop löschen',
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
		'ts-min-3' => [
			'type' => 'boolean',
			'default' => false,
			'description' => 'Mindestbestellmenge bei Endkunden für ts6500-6524 auf 3 setzen',
			'alias' => ['ts']
		],
	],
	'complete-orders' => [
		'limit' => [
			'type' => 'int',
			'default' => 300,
			'description' => 'Anzahl der Bestellungen, die abgeschlossen werden',
			'alias' => ['l']
		]
	],
];
