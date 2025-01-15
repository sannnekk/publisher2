<?php

declare(strict_types=1);

namespace HMnet\Publisher2\Model\Search;

enum SearchType: string
{
	case CONTAINS = 'contains';
	case EQUALS_ANY = 'equalsAny';
	case EQUALS_ALL = 'equalsAll';
	case PREFIX = 'prefix';
	case SUFFIX = 'suffix';
}
