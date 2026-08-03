<?php

declare(strict_types=1);

/*
 * This file is part of the Valkyrja PHPStan package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Valkyrja\PhpStan;

use Valkyrja\PhpStan\Rule\DataObjectStaticMethodRule;

class Rules
{
    /**
     * Get every rule that this package ships.
     *
     * `rules.neon` registers the same list for PHPStan, and a test asserts that the two agree.
     *
     * @return list<class-string>
     */
    public static function getRules(): array
    {
        return [
            DataObjectStaticMethodRule::class,
        ];
    }
}
