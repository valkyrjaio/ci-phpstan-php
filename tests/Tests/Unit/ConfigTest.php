<?php

declare(strict_types=1);

/*
 * This file is part of the Valkyrja PHPStan package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Valkyrja\PhpStan\Tests\Unit;

use Valkyrja\PhpStan\Cache;
use Valkyrja\PhpStan\Tests\Abstract\PhpStanTestCase;

use function dirname;
use function file_get_contents;

final class ConfigTest extends PhpStanTestCase
{
    public function testCacheFileReturnsTheCacheDirectory(): void
    {
        $config = require_once dirname(__DIR__, 3) . '/cache.php';

        self::assertSame(['parameters' => ['tmpDir' => Cache::getDirectory()]], $config);
    }

    public function testConfigFileIncludesTheCacheFile(): void
    {
        $contents = (string) file_get_contents(dirname(__DIR__, 3) . '/config.neon');

        self::assertStringStartsWith('includes:', $contents);
        self::assertStringContainsString('    - cache.php', $contents);
    }
}
