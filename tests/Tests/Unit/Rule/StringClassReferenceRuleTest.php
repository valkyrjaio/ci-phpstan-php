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

namespace Valkyrja\PhpStan\Tests\Unit\Rule;

use DateTime;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPUnit\Framework\Attributes\DataProvider;
use Valkyrja\Http\Message\Header\Header;
use Valkyrja\Orm\Entity\Contract\EntityContract;
use Valkyrja\PhpStan\Rule\StringClassReferenceRule;
use Valkyrja\PhpStan\Tests\Abstract\PhpStanTestCase;
use Valkyrja\PhpStan\Tests\Fixtures\Entity\Trait\DateableFixtureTrait;
use Valkyrja\PhpStan\Tests\Fixtures\Entity\UserFixture;
use Valkyrja\PhpStan\Tests\Fixtures\Message\StatusCodeEnum;

use function class_exists;
use function enum_exists;
use function interface_exists;
use function trait_exists;

final class StringClassReferenceRuleTest extends PhpStanTestCase
{
    /**
     * A string that names a type which exists.
     *
     * @return array<string, array{class-string}>
     */
    public static function existingTypeProvider(): array
    {
        return [
            'a class'           => [UserFixture::class],
            'an interface'      => [EntityContract::class],
            'a trait'           => [DateableFixtureTrait::class],
            'an enum'           => [StatusCodeEnum::class],
            'a framework class' => [Header::class],
        ];
    }

    /**
     * A string that the rule never reports.
     *
     * @return array<string, array{string}>
     */
    public static function ignoredStringProvider(): array
    {
        return [
            'an empty string'           => [''],
            'a word'                    => ['users'],
            'a sentence'                => ['Param must be header'],
            'a namespace with no class' => ['Valkyrja\Nothing\Here'],
            'a binding key'             => ['io.valkyrja.http.ResponseContract'],
        ];
    }

    /**
     * Run the rule over a string literal.
     *
     * @return list<IdentifierRuleError>
     */
    private static function process(string $value): array
    {
        return self::rule()->processNode(new String_($value), self::createStub(Scope::class));
    }

    /**
     * Build the rule over a reflection provider that answers for the types this test uses.
     */
    private static function rule(): StringClassReferenceRule
    {
        $reflectionProvider = self::createStub(ReflectionProvider::class);

        $reflectionProvider->method('hasClass')
            ->willReturnCallback(
                static fn (string $name): bool => class_exists($name)
                    || interface_exists($name)
                    || trait_exists($name)
                    || enum_exists($name)
            );

        return new StringClassReferenceRule($reflectionProvider);
    }

    /**
     * @param class-string $className
     */
    #[DataProvider('existingTypeProvider')]
    public function testStringThatNamesAnExistingTypeIsReported(string $className): void
    {
        $errors = self::process($className);

        self::assertCount(1, $errors);
        self::assertSame(
            "Reference the class $className with ::class, not a string literal.",
            $errors[0]->getMessage()
        );
        self::assertSame(StringClassReferenceRule::IDENTIFIER, $errors[0]->getIdentifier());
    }

    #[DataProvider('ignoredStringProvider')]
    public function testStringThatDoesNotNameATypeIsNotReported(string $value): void
    {
        self::assertSame([], self::process($value));
    }

    public function testLeadingSeparatorIsReported(): void
    {
        $errors = self::process('\\' . UserFixture::class);

        self::assertCount(1, $errors);
        self::assertSame(
            'Reference the class ' . UserFixture::class . ' with ::class, not a string literal.',
            $errors[0]->getMessage()
        );
    }

    public function testGlobalTypeWithNoNamespaceIsNotReported(): void
    {
        // A word is a word far more often than it is a class reference, so the rule needs a
        // namespace separator before it reads a string as a class name.
        self::assertSame([], self::process(DateTime::class));
    }

    public function testGetNodeTypeIsString(): void
    {
        self::assertSame(String_::class, self::rule()->getNodeType());
    }
}
