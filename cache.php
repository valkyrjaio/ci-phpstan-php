<?php

declare(strict_types=1);

/*
 * This file is part of the Valkyrja PHPStan package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

use Valkyrja\PhpStan\Cache;

// PHPStan reads a configuration file before it loads the autoloader of the project.
require_once __DIR__ . '/src/PhpStan/Cache.php';

return [
    'parameters' => [
        'tmpDir' => Cache::getDirectory(),
    ],
];
