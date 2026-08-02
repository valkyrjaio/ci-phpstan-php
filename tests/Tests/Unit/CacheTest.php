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

use ReflectionClass;
use Valkyrja\PhpStan\Cache;
use Valkyrja\PhpStan\Tests\Abstract\PhpStanTestCase;

use function dirname;
use function md5;
use function sys_get_temp_dir;

final class CacheTest extends PhpStanTestCase
{
    public function testGetDirectoryBelongsToOneProject(): void
    {
        $directory = dirname(new ReflectionClass(Cache::class)->getFileName() ?: '');

        self::assertSame(
            sys_get_temp_dir() . '/valkyrja-phpstan/' . md5($directory),
            Cache::getDirectory()
        );
    }

    public function testGetDirectoryIsNotTheDefaultOfPhpStan(): void
    {
        self::assertNotSame(sys_get_temp_dir() . '/phpstan', Cache::getDirectory());
    }
}
