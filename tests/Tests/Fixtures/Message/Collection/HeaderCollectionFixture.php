<?php

declare(strict_types=1);

/*
 * This file is part of the Valkyrja PHPStan package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Valkyrja\PhpStan\Tests\Fixtures\Message\Collection;

/**
 * A data object that holds a private static method.
 */
final class HeaderCollectionFixture
{
    private static function validateHeader(mixed $param): void
    {
    }
}
