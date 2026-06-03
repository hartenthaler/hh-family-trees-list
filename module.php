<?php

/**
 * TreesListModule.
 */

declare(strict_types=1);

namespace TreesListModule;

use Fisharebest\Webtrees\Registry;

require __DIR__ . '/TreesListModule.php';

return Registry::container()->get(TreesListModule::class);
