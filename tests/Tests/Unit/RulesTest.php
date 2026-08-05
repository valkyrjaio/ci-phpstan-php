<?php

declare(strict_types=1);

/*
 * This file is part of the Valkyrja PHPStan package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Valkyrja\PhpStan\Tests\Unit;

use Valkyrja\PhpStan\Rule\DataObjectStaticMethodRule;
use Valkyrja\PhpStan\Rule\StringClassReferenceRule;
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
                StringClassReferenceRule::class,
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
