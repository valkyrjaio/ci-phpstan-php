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

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPUnit\Framework\Attributes\DataProvider;
use Valkyrja\PhpStan\Rule\DataObjectStaticMethodRule;
use Valkyrja\PhpStan\Tests\Abstract\PhpStanTestCase;

final class DataObjectStaticMethodRuleTest extends PhpStanTestCase
{
    /**
     * @return array<string, array{non-empty-string}>
     */
    public static function holderSegmentProvider(): array
    {
        return [
            'factory below a type'     => ['Valkyrja\Type\Uuid\Factory'],
            'support below a type'     => ['Valkyrja\Type\Array\Support'],
            'constant below a message' => ['Valkyrja\Http\Message\Constant'],
            'enum below a message'     => ['Valkyrja\Http\Message\Enum'],
            'provider below an entity' => ['Valkyrja\Auth\Entity\Provider'],
        ];
    }

    /**
     * @return array<string, array{non-empty-string}>
     */
    public static function ownTypeReturnProvider(): array
    {
        return [
            'static'             => ['static'],
            'self'               => ['self'],
            'uppercase static'   => ['STATIC'],
            'own short name'     => ['StringT'],
            'own qualified name' => ['\Valkyrja\Type\String\StringT'],
        ];
    }

    /**
     * @return array<string, array{non-empty-string}>
     */
    public static function foreignTypeReturnProvider(): array
    {
        return [
            'scalar'        => ['string'],
            'array'         => ['array'],
            'another class' => ['\Valkyrja\Type\Int\IntT'],
            'nullable'      => ['?string'],
            'union'         => ['string|int'],
        ];
    }

    /**
     * @param non-empty-string $namespace
     * @param non-empty-string $code
     *
     * @return list<ClassLike>
     */
    private static function parse(string $namespace, string $code): array
    {
        $parser     = new ParserFactory()->createForNewestSupportedVersion();
        $statements = $parser->parse("<?php namespace $namespace; $code");

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
        $errors = $this->process(
            'Valkyrja\Auth\Entity',
            'class User { public static function getTableName(): string { return "users"; } }'
        );

        self::assertCount(1, $errors);
        self::assertSame(
            'Data object Valkyrja\Auth\Entity\User must not have the static method getTableName().',
            $errors[0]->getMessage()
        );
        self::assertSame(DataObjectStaticMethodRule::IDENTIFIER, $errors[0]->getIdentifier());
    }

    public function testEveryStaticMethodIsReported(): void
    {
        $errors = $this->process(
            'Valkyrja\Orm\Entity\Contract',
            'interface EntityContract {
                public static function getTableName(): string;
                public static function getIdField(): string;
            }'
        );

        self::assertCount(2, $errors);
    }

    public function testOnlyTheStaticMethodThatReturnsAnotherTypeIsReported(): void
    {
        $errors = $this->process(
            'Valkyrja\Type\String',
            'class StringT {
                public static function fromValue(mixed $value): static { return new static(); }
                public function asValue(): string { return ""; }
                public static function getCastings(): array { return []; }
            }'
        );

        self::assertCount(1, $errors);
        self::assertSame(
            'Data object Valkyrja\Type\String\StringT must not have the static method getCastings().',
            $errors[0]->getMessage()
        );
    }

    public function testProtectedStaticMethodIsReported(): void
    {
        $errors = $this->process(
            'Valkyrja\Http\Message\Header\Collection',
            'class HeaderCollection { protected static function validateHeader(mixed $param): void {} }'
        );

        self::assertCount(1, $errors);
    }

    public function testTraitIsReported(): void
    {
        $errors = $this->process(
            'Valkyrja\Orm\Entity\Trait',
            'trait Dateable { public static function getDateFormat(): string { return "Y"; } }'
        );

        self::assertCount(1, $errors);
    }

    public function testAbstractStaticMethodIsReported(): void
    {
        $errors = $this->process(
            'Valkyrja\Orm\Entity\Abstract',
            'abstract class Entity { abstract public static function getTableName(): string; }'
        );

        self::assertCount(1, $errors);
    }

    public function testInstanceMethodIsNotReported(): void
    {
        $errors = $this->process(
            'Valkyrja\Auth\Entity',
            'class User { public function getTableName(): string { return "users"; } }'
        );

        self::assertSame([], $errors);
    }

    public function testEnumIsNotReported(): void
    {
        $errors = $this->process(
            'Valkyrja\Http\Message',
            'enum StatusCode { public static function names(): array { return []; } }'
        );

        self::assertSame([], $errors);
    }

    public function testAnonymousClassIsNotReported(): void
    {
        $errors = $this->process(
            'Valkyrja\Auth\Entity',
            '$user = new class { public static function getTableName(): string { return "users"; } };'
        );

        self::assertSame([], $errors);
    }

    public function testNonDataObjectIsNotReported(): void
    {
        $errors = $this->process(
            'Valkyrja\Http\Routing\Matcher',
            'class Matcher { public static function make(): string { return "x"; } }'
        );

        self::assertSame([], $errors);
    }

    /**
     * @param non-empty-string $namespace
     */
    #[DataProvider('holderSegmentProvider')]
    public function testHolderSegmentBelowADataSegmentIsNotReported(string $namespace): void
    {
        $errors = $this->process(
            $namespace,
            'class Holder { public static function generate(): string { return "x"; } }'
        );

        self::assertSame([], $errors);
    }

    /**
     * @param non-empty-string $returnType
     */
    #[DataProvider('ownTypeReturnProvider')]
    public function testNamedConstructorIsNotReported(string $returnType): void
    {
        $errors = $this->process(
            'Valkyrja\Type\String',
            "class StringT { public static function fromValue(mixed \$value): $returnType { return new static(); } }"
        );

        self::assertSame([], $errors);
    }

    /**
     * @param non-empty-string $returnType
     */
    #[DataProvider('foreignTypeReturnProvider')]
    public function testMethodThatReturnsAnotherTypeIsReported(string $returnType): void
    {
        $errors = $this->process(
            'Valkyrja\Type\String',
            "class StringT { public static function getX(): $returnType { throw new \Exception(); } }"
        );

        self::assertCount(1, $errors);
    }

    public function testMethodWithoutAReturnTypeIsReported(): void
    {
        $errors = $this->process(
            'Valkyrja\Auth\Entity',
            'class User { public static function getTableName() { return "users"; } }'
        );

        self::assertCount(1, $errors);
    }

    /**
     * @param non-empty-string $namespace
     * @param non-empty-string $code
     *
     * @return list<IdentifierRuleError>
     */
    private function process(string $namespace, string $code): array
    {
        $rule   = new DataObjectStaticMethodRule();
        $scope  = self::createStub(Scope::class);
        $errors = [];

        foreach (self::parse($namespace, $code) as $classLike) {
            foreach ($rule->processNode($classLike, $scope) as $error) {
                $errors[] = $error;
            }
        }

        return $errors;
    }
}
