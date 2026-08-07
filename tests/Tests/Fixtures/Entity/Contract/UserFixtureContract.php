<?php

declare(strict_types=1);

/*
 * This file is part of the Valkyrja PHPStan package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Valkyrja\PhpStan\Tests\Fixtures\Entity\Contract;

/**
 * A contract that declares a static method.
 */
interface UserFixtureContract
{
    public static function getUsernameField(): string;

    public static function getPasswordField(): string;
}
