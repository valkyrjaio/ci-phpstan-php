<?php

declare(strict_types=1);

/*
 * This file is part of the Valkyrja PHPStan package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Valkyrja\PhpStan\Tests\Fixtures\Type;

use Valkyrja\PhpStan\Tests\Fixtures\Message\ReturnTypeFixture as MessageReturnTypeFixture;
use Valkyrja\PhpStan\Tests\Fixtures\Routing\MatcherFixture;

final class ReturnTypeFixture
{
    public static function fromStatic(): static
    {
        return new self();
    }

    public static function fromSelf(): self
    {
        return new self();
    }

    public static function fromShortName(): self
    {
        return new self();
    }

    public static function getString(): string
    {
        return '';
    }

    public static function getArray(): array
    {
        return [];
    }

    public static function getSameShortNameInAnotherNamespace(): MessageReturnTypeFixture
    {
        return new MessageReturnTypeFixture();
    }

    public static function getAnotherType(): MatcherFixture
    {
        return new MatcherFixture();
    }

    public static function getNullable(): string|null
    {
        return null;
    }

    public static function getUnion(): string|int
    {
        return 0;
    }

    public static function getUntyped(): void
    {
    }
}
