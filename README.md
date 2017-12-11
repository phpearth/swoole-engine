# Swoole Engine

[![Build Status](https://img.shields.io/travis/php-earth/swoole-engine/master.svg?style=flat-square)](https://travis-ci.org/php-earth/swoole-engine)

Event-driven PHP engine for running PHP Applications with [Swoole extension](http://swoole.com).

## Installation

<details>
  <summary>Before using this library, you'll need Swoole extension</summary>

  Installing using PECL:

  ```bash
  pecl install swoole
  ```

  Add `extension=swoole` (or `extension=swoole.so` for PHP < 7.2) to your `php.ini`
  file for PHP CLI sapi:

  ```bash
  echo "extension=swoole" | sudo tee --append `php -r 'echo php_ini_loaded_file();'`
  ```

  Check if Swoole extension is loaded
  ```bash
  php --ri swoole
  ```

  You should see something like

  ```bash
  swoole

  swoole support => enabled
  Version => 2.0.10
  Author => tianfeng.han[email: mikan.tenny@gmail.com]
  epoll => enabled
  eventfd => enabled
  timerfd => enabled
  signalfd => enabled
  cpu affinity => enabled
  spinlock => enabled
  rwlock => enabled
  async http/websocket client => enabled
  Linux Native AIO => enabled
  pcre => enabled
  mutex_timedlock => enabled
  pthread_barrier => enabled
  futex => enabled

  Directive => Local Value => Master Value
  swoole.aio_thread_num => 2 => 2
  swoole.display_errors => On => On
  swoole.use_namespace => On => On
  swoole.fast_serialize => Off => Off
  swoole.unixsock_buffer_size => 8388608 => 8388608
  ```

</details>

Then proceed and install Swoole Engine library in your project with Composer:

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
