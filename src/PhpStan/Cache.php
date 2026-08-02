<?php

declare(strict_types=1);

/*
 * This file is part of the Valkyrja PHPStan package.
 *
 * Copyright (c) 2016-present Melech Mizrachi
 *
 * Released under the MIT License. See LICENSE.md for details.
 */

namespace Valkyrja\PhpStan;

use function md5;
use function sys_get_temp_dir;

class Cache
{
    /**
     * Get a cache directory that belongs to one project.
     *
     * PHPStan defaults every project on a machine to `sys_get_temp_dir() . '/phpstan'`. A cache
     * entry holds an absolute path that reaches inside `phpstan.phar`. That `phar` belongs to the
     * project that wrote the entry. A second project then reads a path that it does not own.
     *
     * The failure appears when the first project moves or goes away. PHPStan reports that a file
     * inside a `phar` "is not a file", and it names a directory of another repository. A git
     * worktree makes this common, because a developer removes a worktree when the work ends.
     *
     * One directory covers every PHPStan cache. The result cache sits at `%tmpDir%/resultCache.php`
     * and the compiled container sits below `%tmpDir%/cache`, so a single value isolates both.
     *
     * The directory name comes from `__DIR__`, because Composer installs this package into the
     * `vendor` directory of each project. The path therefore identifies one project, and it stays
     * the same when the developer runs PHPStan from another directory.
     *
     * Warning: `__DIR__` resolves a symbolic link. Two projects that share one checkout through a
     * Composer `path` repository therefore share one cache directory.
     *
     * @return non-empty-string
     */
    public static function getDirectory(): string
    {
        return sys_get_temp_dir() . '/valkyrja-phpstan/' . md5(__DIR__);
    }
}
