<?php

declare(strict_types=1);

/*
 * This file is part of the Valkyrja PHPStan package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Valkyrja\PhpStan\Tests\Fixtures\Type\Support;

/**
 * A support class that holds a static method below a type segment.
 */
final class ArrayFixture
{
    public static function flatten(array $value): array
    {
        return $value;
    }
}
