# Sessions

Swoole extension out of the box doesn't support sessions the way we're used to.

In traditional PHP, the `session_start()` call also sends a cookie to a client
containing the session_id. By default cookie name is `PHPSESSID`.

When `session_start` is called inside a Swoole request event, note that this is
happening concurrently so it will be called again on each request inside a running
PHP file:

```php
$http = new \swoole_http_server('127.0.0.1', 9501);

$http->on('request', function (\swoole_http_request $request, \swoole_http_response $response) {
    session_start();

    $_SESSION['key'] = $_SESSION['key'] ?? rand();

    $response->end('Session key value: '.$_SESSION['key'].'<br>Session name: '.session_name().'<br>Session ID: '.session_id());
});

$http->start();
```

```text
Notice: A session had already been started - ignoring session_start() in ...
```

So we must adjust this a bit:

```php
$http->on('request', function (\swoole_http_request $request, \swoole_http_response $response) {
    if (session_status() != PHP_SESSION_ACTIVE) {
        session_start();
    }

    $_SESSION['key'] = $_SESSION['key'] ?? rand();

    $response->end('Session key value: '.$_SESSION['key'].'<br>Session name: '.session_name().'<br>Session ID: '.session_id());
});
```

Much better. Now `session_start()` will be called only once. However note that
sessions don't work as you might expect with Swoole and each time you need to
regenerate session id as well and set the session cookie. How to approach this:

```php
$http->on('request', function (\swoole_http_request $request, \swoole_http_response $response) {
    if (session_status() != PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (isset($request->cookie[session_name()])) {
        // Client has session cookie set, but Swoole might have session_id() from some
        // other request, so we need to regenerate it
        session_id($request->cookie[session_name()]);
    } else {
        $params = session_get_cookie_params();

        if (session_id()) {
            session_id(\bin2hex(\random_bytes(32)));
        }
        $_SESSION = [];

        $response->rawcookie(
            session_name(),
            session_id(),
            $params['lifetime'] ? time() + $params['lifetime'] : null,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    $_SESSION['key'] = $_SESSION['key'] ?? rand();

    $response->end('Session key value: '.$_SESSION['key'].'<br>Session name: '.session_name().'<br>Session ID: '.session_id());
});
```
