# Swoole Engine

[![Build Status](https://img.shields.io/travis/php-earth/swoole-engine/master.svg?style=flat-square)](https://travis-ci.org/php-earth/swoole-engine)

Event-driven PHP engine for running PHP Applications with [Swoole extension](http://swoole.com).

## Installation

```bash
composer require php-earth/swoole-engine
```

## Usage

Currently supported frameworks:

* Symfony:

```bash
vendor/bin/swoole [--env=dev|prod|...] [--host=IP] [--no-debug]
```

## Documentation

For more information, read the [documentation](docs):

* [Introduction](docs/intro.md)
* [Sessions](docs/sessions.md)

## License

[Contributions](docs/CONTRIBUTING.md) are most welcome. This repository is
released under the [MIT license](LICENSE).
