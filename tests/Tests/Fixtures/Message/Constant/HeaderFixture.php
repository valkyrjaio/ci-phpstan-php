<?php

declare(strict_types=1);

/*
 * This file is part of the Valkyrja PHPStan package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Valkyrja\PhpStan\Tests\Fixtures\Message\Constant;

/**
 * A constant holder that holds a static method below a message segment.
 */
final class HeaderFixture
{
    public static function all(): array
    {
        return [];
    }
}
