# Introduction

Swoole is event-driven PHP extension with HTTP server, websocket server,

Swoole is an event-driven, asynchronous, concurrent networking communication
engine with high performance PHP extension written in C language.

It includes components for different purposes: TCP/UDP Server and Client, Task
Worker, Database Connection Pooling, Millisecond Timer, Event, Async IO, Async
Http/WebSocket Client, Async Redis Client, Async MySQL Client, and Async DNS
Requiring.

Prononuced s+wall - `/swəʊl/`.

## Swoole installation

Installation can be done with PECL as any other PHP extension:

```bash
pecl install swoole
```

In case missing, add the `extension=swoole.so` to the php.ini for PHP CLI. Beware
that there are multiple php.ini files.

## Swoole HTTP Server

We'll start with running a simple HTTP server:

```php
<?php

$http = new \swoole_http_server('127.0.0.1', 9501);

$http->on('request', function (\swoole_http_request $request, \swoole_http_response $response) {
    $response->end("<h1>Hello Swoole. #".rand(1000, 9999)."</h1>");
});

$http->start();
```
