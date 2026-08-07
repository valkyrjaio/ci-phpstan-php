<?php

declare(strict_types=1);

/*
 * This file is part of the Valkyrja PHPStan package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Valkyrja\PhpStan\Tests\Fixtures\Entity\Provider;

/**
 * A provider that holds a static method below an entity segment.
 */
final class UserServiceProviderFixture
{
    public static function publishers(): array
    {
        return [];
    }
}
