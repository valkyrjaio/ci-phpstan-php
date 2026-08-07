<?php

declare(strict_types=1);

/*
 * This file is part of the Valkyrja PHPStan package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Valkyrja\PhpStan\Rule;

use Override;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function array_pop;
use function explode;
use function in_array;
use function strtolower;

/**
 * @implements Rule<ClassLike>
 */
final class DataObjectStaticMethodRule implements Rule
{
    /**
     * The error identifier.
     *
     * @var non-empty-string
     */
    public const string IDENTIFIER = 'valkyrja.dataObjectStaticMethod';

    /**
     * The segments that make a class a data object.
     *
     * @var non-empty-list<non-empty-string>
     */
    public const array DATA_SEGMENTS = ['Config', 'Data', 'Entity', 'Message', 'Model', 'Type'];

    /**
     * The segments that hold a static method, even below a data segment.
     *
     * @var non-empty-list<non-empty-string>
     */
    public const array HOLDER_SEGMENTS = ['Constant', 'Enum', 'Factory', 'Provider', 'Support'];

    /**
     * Determine if a fully qualified name belongs to a data object.
     */
    public static function isDataObject(string $fullyQualifiedName): bool
    {
        $segments = explode('\\', $fullyQualifiedName);

        // The last segment is the class name, which never makes a class a data object.
        array_pop($segments);

        $isData = false;

        foreach ($segments as $segment) {
            if (in_array($segment, self::HOLDER_SEGMENTS, true)) {
                return false;
            }

            if (in_array($segment, self::DATA_SEGMENTS, true)) {
                $isData = true;
            }
        }

        return $isData;
    }

    /**
     * Determine if a method returns the type that declares it.
     */
    private static function returnsOwnType(ClassMethod $method, string $declaringName): bool
    {
        $returnType = $method->returnType;

        // A nullable type, a union and an intersection each return more than the own type.
        if (! $returnType instanceof Identifier && ! $returnType instanceof Name) {
            return false;
        }

        $type = $returnType->toString();

        if (in_array(strtolower($type), ['static', 'self'], true)) {
            return true;
        }

        // Two data objects in different namespaces can share a short name.
        return $type === $declaringName;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function getNodeType(): string
    {
        return ClassLike::class;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        // An enum is exempt. Every language gives an enum static members of its own.
        if ($node instanceof Enum_) {
            return [];
        }

        $name = $node->namespacedName;

        // An anonymous class has no name, so it has no segment to read.
        if ($name === null) {
            return [];
        }

        $fullyQualifiedName = $name->toString();

        if (! self::isDataObject($fullyQualifiedName)) {
            return [];
        }

        $errors = [];

        foreach ($node->getMethods() as $method) {
            if (! $method->isStatic()) {
                continue;
            }

            // A named constructor returns its own type, so the data object keeps it.
            if (self::returnsOwnType($method, $fullyQualifiedName)) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(
                "Data object $fullyQualifiedName must not have the static method {$method->name->toString()}()."
            )
                ->identifier(self::IDENTIFIER)
                ->line($method->getStartLine())
                ->tip(
                    'A factory takes construction, and a support class takes a calculation or a rendering. '
                    . 'A named constructor that returns its own type stays.'
                )
                ->build();
        }

        return $errors;
    }
}
