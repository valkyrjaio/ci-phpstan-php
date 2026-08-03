<?php

declare(strict_types=1);

/*
 * This file is part of the Valkyrja PHPStan package.
 *
 * (c) Melech Mizrachi <melechmizrachi@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Valkyrja\PhpStan\Tests\Fixtures\Type;

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
