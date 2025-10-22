# Development

## Getting started

Clone repository:

```sh
git clone git@github.com:ReunMedia/nanomanager.git
cd nanomanager
```

Install dependencies:

```sh
bun install
composer install
composer install --working-dir=packages/php
```

Start development servers:

```sh
bun moon :dev
```

Open [`http://localhost:5173/`](http://localhost:5173/) in your browser.

## Development tasks

This project uses [moon](https://moonrepo.dev/moon) as task runner. Use `bun
moon query projects` to list all projects and `bun moon project <project>` to
list tasks for a specific project.

## Git hooks

Git hooks are not enabled by default. Run `bun moon sync hooks` to enable them.

## Package integration tests

`tests/integration` directory contains manual tests for different methods of
integrating Nanomanager. Here's how you run them:

```sh
cd tests/integration/<...-test>
composer install
bun moon frontend:build
bun moon php:build # Required for PHAR test only
composer dev
```

All integration tests use built version of frontend so you must run `bun moon frontend:build` again after making any changes.

## Releasing new version

See [Release Workflow](release-workflow.md) on how to publish a new release.
