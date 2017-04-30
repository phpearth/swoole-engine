# swoole_atomic

## Atomic operation for Swoole server.

The `swoole_atomic` uses shared memory and operates between different processes.
It is GCC based CPU atomic instructions provided, without locking. Must be created
before starting Swoole server in order to be used on the worker process.

Included methods:

* `__construct`

* `add`

* `sub`

* `get`

* `set`

* `cmpset`
