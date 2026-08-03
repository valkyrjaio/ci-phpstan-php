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

namespace Valkyrja\PhpStan\Tests\Unit;

use Valkyrja\PhpStan\Rule\DataObjectStaticMethodRule;
use Valkyrja\PhpStan\Rules;
use Valkyrja\PhpStan\Tests\Abstract\PhpStanTestCase;

use function file_get_contents;
use function str_contains;

final class RulesTest extends PhpStanTestCase
{
    /**
     * The path to the config that PHPStan reads.
     *
     * @var non-empty-string
     */
    private const string RULES_NEON = __DIR__ . '/../../../rules.neon';

    public function testGetRulesReturnsEveryRule(): void
    {
        self::assertSame(
            [
                DataObjectStaticMethodRule::class,
            ],
            Rules::getRules()
        );
    }

    public function testRulesNeonRegistersEveryRule(): void
    {
        $neon = file_get_contents(self::RULES_NEON);

        self::assertNotFalse($neon);

        foreach (Rules::getRules() as $rule) {
            self::assertTrue(
                str_contains($neon, $rule),
                "rules.neon does not register $rule"
            );
        }
    }
}
