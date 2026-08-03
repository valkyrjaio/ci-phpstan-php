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
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function ltrim;
use function str_contains;

/**
 * A class reference uses `::class`. It never uses a string literal.
 *
 * @implements Rule<String_>
 */
final class StringClassReferenceRule implements Rule
{
    /**
     * The error identifier.
     *
     * @var non-empty-string
     */
    public const string IDENTIFIER = 'valkyrja.stringClassReference';

    public function __construct(
        private ReflectionProvider $reflectionProvider
    ) {
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function getNodeType(): string
    {
        return String_::class;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        $value = ltrim($node->value, '\\');

        // A string only names a class when it is fully qualified, because PHP never resolves a
        // string against the use statements of a file. A string with no namespace separator is
        // data, and a word such as "Exception" is a word far more often than it is a reference.
        if (! str_contains($value, '\\')) {
            return [];
        }

        if (! $this->reflectionProvider->hasClass($value)) {
            return [];
        }

        return [
            RuleErrorBuilder::message("Reference the class $value with ::class, not a string literal.")
                ->identifier(self::IDENTIFIER)
                ->tip(
                    'PHP resolves ::class against the use statements of the file, so a rename keeps it correct. '
                    . 'A string literal is text that no tool checks.'
                )
                ->build(),
        ];
    }
}
