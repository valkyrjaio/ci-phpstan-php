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

namespace Valkyrja\PhpStan\Tests\Fixtures\Entity;

final class UserFixture
{
    public static function getTableName(): string
    {
        return 'users';
    }

    public function getName(): string
    {
        return 'name';
    }
}
