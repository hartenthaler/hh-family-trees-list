<?php

/**
 * TreesListModule.
 */

declare(strict_types=1);

namespace TreesListModule;

use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Services\HtmlService;
use Fisharebest\Webtrees\Services\TreeService;

require __DIR__ . '/MoreI18N.php';
require __DIR__ . '/TreesListModule.php';

return new TreesListModule(
    Registry::container()->get(HtmlService::class),
    Registry::container()->get(TreeService::class),
);
