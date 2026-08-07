<?php

declare(strict_types=1);

/*
 * This file is part of the Valkyrja PHPStan package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Valkyrja\PhpStan\Tests\Fixtures\Routing;

/**
 * A class that holds a static method below no data segment.
 */
final class MatcherFixture
{
    public static function make(): string
    {
        return 'matcher';
    }
}
