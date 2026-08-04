<p align="center"><a href="https://valkyrja.io" target="_blank">
    <img src="https://raw.githubusercontent.com/valkyrjaio/art/refs/heads/master/long-banner/orange/php.png" width="100%">
</a></p>

# Valkyrja PHPStan

Shared PHPStan configuration for Valkyrja PHP projects — a reference
configuration and reusable workflow that enforce consistent static analysis
across consuming repositories.

<p>
    <a href="https://packagist.org/packages/valkyrja/ci-phpstan"><img src="https://poser.pugx.org/valkyrja/ci-phpstan/require/php" alt="PHP Version Require"></a>
    <a href="https://packagist.org/packages/valkyrja/ci-phpstan"><img src="https://poser.pugx.org/valkyrja/ci-phpstan/v" alt="Latest Stable Version"></a>
    <a href="https://packagist.org/packages/valkyrja/ci-phpstan"><img src="https://poser.pugx.org/valkyrja/ci-phpstan/license" alt="License"></a>
    <a href="https://github.com/valkyrjaio/ci-phpstan-php/actions/workflows/ci.yml?query=branch%3A26.x"><img src="https://github.com/valkyrjaio/ci-phpstan-php/actions/workflows/ci.yml/badge.svg?branch=26.x" alt="CI Status"></a>
    <a href="https://scrutinizer-ci.com/g/valkyrjaio/ci-phpstan-php/?branch=26.x"><img src="https://scrutinizer-ci.com/g/valkyrjaio/ci-phpstan-php/badges/quality-score.png?b=26.x" alt="Scrutinizer"></a>
    <a href="https://coveralls.io/github/valkyrjaio/ci-phpstan-php?branch=26.x"><img src="https://coveralls.io/repos/github/valkyrjaio/ci-phpstan-php/badge.svg?branch=26.x" alt="Coverage Status" /></a>
    <a href="https://shepherd.dev/github/valkyrjaio/ci-phpstan-php"><img src="https://shepherd.dev/github/valkyrjaio/ci-phpstan-php/coverage.svg" alt="Psalm Shepherd" /></a>
    <a href="https://sonarcloud.io/summary/new_code?id=valkyrjaio_phpstan"><img src="https://sonarcloud.io/api/project_badges/measure?project=valkyrjaio_phpstan&metric=sqale_rating" alt="Maintainability Rating" /></a>
</p>

Usage
-----

Run via the root Composer script:

```
composer phpstan
```

This delegates to `vendor/bin/phpstan --memory-limit=-1` inside the CI
directory, using the `phpstan.neon` configuration.

Configuration
-------------

The CI directory ships with a `phpstan.neon` that serves as the reference
configuration. Key settings:

| Setting                     | Value   | Effect                                                         |
| --------------------------- | ------- | -------------------------------------------------------------- |
| `level`                     | `9`     | Strictest level — all rule categories enabled                  |
| `treatPhpDocTypesAsCertain` | `false` | PHPDoc types are not blindly trusted; prevents false negatives |

### Shared Configuration

The package ships a `config.neon` with the settings that every Valkyrja project
shares. Include it from the `phpstan.neon` in the CI directory:

```neon
includes:
    - phpstan-baseline.neon
    - vendor/valkyrja/ci-phpstan/config.neon
```

### Cache Directory

PHPStan writes its cache to `sys_get_temp_dir() . '/phpstan'`, so every project
on a machine writes to one directory. A cache entry holds an absolute path that
reaches inside `phpstan.phar`. That `phar` belongs to the project that wrote the
entry. A second project then reads a path that it does not own.

The failure appears when the first project moves or goes away. PHPStan reports
that a file inside a `phar` "is not a file", and it names a directory of another
repository.

`config.neon` prevents this failure. It sets `tmpDir` to a directory that
belongs to one project:

```php
sys_get_temp_dir() . '/valkyrja-phpstan/' . md5(__DIR__)
```

`Valkyrja\PhpStan\Cache::getDirectory()` builds the path. `__DIR__` identifies
one project, because Composer installs this package into the `vendor` directory
of each project. One directory holds every PHPStan cache, because the result
cache and the compiled container both sit below `tmpDir`.

Warning: `__DIR__` resolves a symbolic link. Two projects that share one
checkout through a Composer `path` repository therefore share one cache
directory, and the failure above comes back.

Nothing removes a cache directory. A project that goes away leaves its
directory, so remove the directories at intervals:

```bash
rm -rf "$(php -r 'echo sys_get_temp_dir();')/valkyrja-phpstan"
```

### Scanned Paths

| Path   | Included |
| ------ | -------- |
| `src/` | Yes      |

### Baseline

Known issues are tracked in `phpstan-baseline.neon`. Regenerate it with:

```
vendor/bin/phpstan --generate-baseline
```

### Bootstrap

An `autoload.php` is required in the CI directory to bootstrap the project
autoloader before PHPStan analyses the source.

Workflows
---------

The [`_workflow-call.yml`](.github/workflows/_workflow-call.yml) reusable
workflow runs PHPStan against the calling repository's source. It is
designed to be called from other repositories via `workflow_call`.

### Inputs

| Input                  | Type    | Default                | Description                                                                                                                                           |
| ---------------------- | ------- | ---------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------- |
| `paths`                | string  | —                      | **Required.** YAML filter spec with two keys: `ci` (CI config files that trigger a base-branch fetch) and `files` (all files that trigger the check). |
| `post-pr-comment`      | boolean | `true`                 | Post a PR comment on failure and remove it on success. Disable when the calling workflow handles its own reporting.                                   |
| `composer-options`     | string  | `''`                   | Extra flags passed to every `composer install` step (e.g. `--ignore-platform-req=ext-openswoole`).                                                    |
| `php-version`          | string  | `'8.4'`                | PHP version to use.                                                                                                                                   |
| `ci-directory`         | string  | `'.github/ci/phpstan'` | Path to the CI directory containing `composer.json` and the tool config.                                                                              |
| `extensions`           | string  | `'mbstring, intl'`     | PHP extensions to install via `shivammathur/setup-php`.                                                                                               |
| `additional-directory` | string  | `''`                   | Path to an additional Composer dependencies directory to install before running PHPStan. Leave empty to skip.                                         |

### Usage

```yaml
jobs:
  phpstan:
    uses: valkyrjaio/ci-phpstan-php/.github/workflows/_workflow-call.yml@26.x
    permissions:
      pull-requests: write
      contents: read
    with:
      php-version: '8.4'
      paths: |
        ci:
          - '.github/ci/phpstan/**'
          - '.github/workflows/phpstan.yml'
        files:
          - '.github/ci/phpstan/**'
          - '.github/workflows/phpstan.yml'
          - 'src/**/*.php'
          - 'composer.json'
    secrets: inherit
```

`secrets: inherit` is required to pass the `VALKYRJA_GHA_APP_ID` and
`VALKYRJA_GHA_PRIVATE_KEY` org secrets used for PR comments.

Contributing
------------

See [`CONTRIBUTING.md`][contributing url] for the submission process and
[`VOCABULARY.md`][vocabulary url] for the terminology used across Valkyrja.

Security Issues
---------------

If you discover a security vulnerability, please follow our
[disclosure procedure][security vulnerabilities url].

License
-------

Licensed under the [MIT license][MIT license url]. See
[`LICENSE.md`](./LICENSE.md).

[contributing url]: https://github.com/valkyrjaio/.github/blob/26.x/CONTRIBUTING.md
[vocabulary url]: https://github.com/valkyrjaio/.github/blob/26.x/VOCABULARY.md
[security vulnerabilities url]: https://github.com/valkyrjaio/.github/blob/26.x/SECURITY.md
[MIT license url]: https://opensource.org/licenses/MIT
