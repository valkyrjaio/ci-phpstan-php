<?php

declare(strict_types=1);

/*
 * This file is part of the Valkyrja PHPStan package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Valkyrja\PhpStan\Tests\Fixtures\Message\Enum;

/**
 * A class that holds a static method below an enum segment.
 */
final class StatusFixture
{
    public static function all(): array
    {
        return [];
    }
}
