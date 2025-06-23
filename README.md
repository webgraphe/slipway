# webgraphe/slipway
Fast Docker Composition for PHP Projects development.

## Install

```shell
composer require webgraphe/slipway --dev
```

## How it works

Run the command below to generate the boilerplate files for `docker-compose`.

```shell
vendor/bin/slipway <PROJECTNAME>
```

This will generate a `docker-compose.yml` file in the current working directory and a `.docker` folder with docker files
to build images for containers.

To regenerate over existing files, use `--force` option.

```shell
vendor/bin/slipway <PROJECTNAME> --force
```

Then:
```shell
docker compose up -d
```
