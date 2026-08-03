<?php

declare(strict_types=1);

/*
 * This file is part of the Valkyrja PHPStan package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Valkyrja\PhpStan\Tests\Unit\Rule;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Valkyrja\Http\Message\Header\Header;
use Valkyrja\Http\Routing\Matcher\Matcher;
use Valkyrja\Orm\Entity\Contract\EntityContract;
use Valkyrja\PhpStan\Rule\DataObjectStaticMethodRule;
use Valkyrja\PhpStan\Tests\Abstract\PhpStanTestCase;
use Valkyrja\PhpStan\Tests\Fixtures\Entity\Abstract\EntityFixture;
use Valkyrja\PhpStan\Tests\Fixtures\Entity\AnonymousFixture;
use Valkyrja\PhpStan\Tests\Fixtures\Entity\Contract\UserFixtureContract;
use Valkyrja\PhpStan\Tests\Fixtures\Entity\Provider\UserServiceProviderFixture;
use Valkyrja\PhpStan\Tests\Fixtures\Entity\ProviderlessFixture;
use Valkyrja\PhpStan\Tests\Fixtures\Entity\Trait\DateableFixtureTrait;
use Valkyrja\PhpStan\Tests\Fixtures\Entity\UserFixture;
use Valkyrja\PhpStan\Tests\Fixtures\Message\Collection\HeaderCollectionFixture;
use Valkyrja\PhpStan\Tests\Fixtures\Message\Constant\HeaderFixture;
use Valkyrja\PhpStan\Tests\Fixtures\Message\StatusCodeEnum;
use Valkyrja\PhpStan\Tests\Fixtures\Routing\MatcherFixture;
use Valkyrja\PhpStan\Tests\Fixtures\Type\Factory\UuidFactoryFixture;
use Valkyrja\PhpStan\Tests\Fixtures\Type\ReturnTypeFixture;
use Valkyrja\PhpStan\Tests\Fixtures\Type\Support\ArrayFixture;
use Valkyrja\Type\String\StringT;

use function file_get_contents;
use function sort;

final class DataObjectStaticMethodRuleTest extends PhpStanTestCase
{
    /**
     * A class that holds a static method, even below a data segment.
     *
     * @return array<string, array{class-string}>
     */
    public static function holderSegmentProvider(): array
    {
        return [
            'factory below a type'     => [UuidFactoryFixture::class],
            'support below a type'     => [ArrayFixture::class],
            'constant below a message' => [HeaderFixture::class],
            'provider below an entity' => [UserServiceProviderFixture::class],
        ];
    }

    /**
     * A class that the rule never reports.
     *
     * @return array<string, array{class-string}>
     */
    public static function exemptClassProvider(): array
    {
        return [
            'an enum'             => [StatusCodeEnum::class],
            'an anonymous class'  => [AnonymousFixture::class],
            'no data segment'     => [MatcherFixture::class],
            'no static method'    => [ProviderlessFixture::class],
            'a framework type'    => [StringT::class],
            'a framework header'  => [Header::class],
            'a framework matcher' => [Matcher::class],
        ];
    }

    /**
     * Run the rule over the file that declares a class.
     *
     * @param class-string $className
     *
     * @return list<IdentifierRuleError>
     */
    private static function process(string $className): array
    {
        $rule   = new DataObjectStaticMethodRule();
        $scope  = self::createStub(Scope::class);
        $errors = [];

        foreach (self::parse($className) as $classLike) {
            foreach ($rule->processNode($classLike, $scope) as $error) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    /**
     * Get every message that the rule reports for a class, in a stable order.
     *
     * @param class-string $className
     *
     * @return list<string>
     */
    private static function messages(string $className): array
    {
        $messages = [];

        foreach (self::process($className) as $error) {
            $messages[] = $error->getMessage();
        }

        sort($messages);

        return $messages;
    }

    /**
     * Parse the file that declares a class into its class like nodes.
     *
     * @param class-string $className
     *
     * @return list<ClassLike>
     */
    private static function parse(string $className): array
    {
        $fileName = new ReflectionClass($className)->getFileName();

        self::assertNotFalse($fileName);

        $source = file_get_contents($fileName);

        self::assertNotFalse($source);

        $parser     = new ParserFactory()->createForNewestSupportedVersion();
        $statements = $parser->parse($source);

        self::assertNotNull($statements);

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $statements = $traverser->traverse($statements);

        $collector = new class extends NodeVisitorAbstract {
            /** @var list<ClassLike> */
            public array $classLikes = [];

            public function enterNode(Node $node): null
            {
                if ($node instanceof ClassLike) {
                    $this->classLikes[] = $node;
                }

                return null;
            }
        };

        $collecting = new NodeTraverser();
        $collecting->addVisitor($collector);
        $collecting->traverse($statements);

        return $collector->classLikes;
    }

    public function testGetNodeTypeIsClassLike(): void
    {
        self::assertSame(ClassLike::class, new DataObjectStaticMethodRule()->getNodeType());
    }

    public function testStaticMethodOnAnEntityIsReported(): void
    {
        $errors = self::process(UserFixture::class);

        self::assertCount(1, $errors);
        self::assertSame(
            'Data object ' . UserFixture::class . ' must not have the static method getTableName().',
            $errors[0]->getMessage()
        );
        self::assertSame(DataObjectStaticMethodRule::IDENTIFIER, $errors[0]->getIdentifier());
    }

    public function testEveryStaticMethodOnAContractIsReported(): void
    {
        self::assertSame(
            [
                'Data object ' . UserFixtureContract::class . ' must not have the static method getPasswordField().',
                'Data object ' . UserFixtureContract::class . ' must not have the static method getUsernameField().',
            ],
            self::messages(UserFixtureContract::class)
        );
    }

    public function testStaticMethodOnATraitIsReported(): void
    {
        self::assertSame(
            ['Data object ' . DateableFixtureTrait::class . ' must not have the static method getDateFormat().'],
            self::messages(DateableFixtureTrait::class)
        );
    }

    public function testAbstractStaticMethodIsReported(): void
    {
        self::assertSame(
            ['Data object ' . EntityFixture::class . ' must not have the static method getIdField().'],
            self::messages(EntityFixture::class)
        );
    }

    public function testProtectedStaticMethodIsReported(): void
    {
        self::assertSame(
            [
                'Data object ' . HeaderCollectionFixture::class
                . ' must not have the static method validateHeader().',
            ],
            self::messages(HeaderCollectionFixture::class)
        );
    }

    public function testOnlyTheStaticMethodThatReturnsAnotherTypeIsReported(): void
    {
        $prefix = 'Data object ' . ReturnTypeFixture::class . ' must not have the static method ';

        self::assertSame(
            [
                $prefix . 'getAnotherType().',
                $prefix . 'getArray().',
                $prefix . 'getNullable().',
                $prefix . 'getString().',
                $prefix . 'getUnion().',
                $prefix . 'getUntyped().',
            ],
            self::messages(ReturnTypeFixture::class)
        );
    }

    /**
     * @param class-string $className
     */
    #[DataProvider('holderSegmentProvider')]
    public function testHolderSegmentBelowADataSegmentIsNotReported(string $className): void
    {
        self::assertSame([], self::process($className));
    }

    /**
     * @param class-string $className
     */
    #[DataProvider('exemptClassProvider')]
    public function testExemptClassIsNotReported(string $className): void
    {
        self::assertSame([], self::process($className));
    }

    public function testFrameworkEntityContractIsReported(): void
    {
        self::assertNotSame([], self::process(EntityContract::class));
    }
}
