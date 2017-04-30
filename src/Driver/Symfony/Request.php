<?php

namespace PhpEarth\Swoole\Driver\Symfony;

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class Request
{
    /**
     * Creates Symfony request from Swoole request. By default Swoole request
     * contains headers with lower case keys and dash separator instead
     * of underscores and upper case as PHP will expect later on in $_SERVER
     * superglobal. For example:
     * - host: localhost:9501
     * - connection: keep-alive
     * - accept-language: en-US,en;q=0.8,sl;q=0.6
     * Also PHP superglobals must get set here.
     *
     * @param  \swoole_http_request $request
     * @return Request
     */
    public function createSymfonyRequest(\swoole_http_request $request) {
        // $_SERVER
        $headers = [];
        foreach ($request->header as $key => $value) {
            $key = str_replace('-', '_', $key);
            $key = ucwords($key, '-');
            $headers['http_' . $key] = $value;
        }
        $_SERVER = array_merge($request->server, $headers);
        // Also change possible remaining keys to uppercase
        $_SERVER = array_change_key_case($_SERVER, CASE_UPPER);
        $request->server = $_SERVER;

        // Other superglobals
        $_GET = $request->get ?? [];
        $_POST = $request->post ?? [];
        $_COOKIE = $request->cookie ?? [];
        $_FILES = $request->files ?? [];
        $content = $request->rawContent() ?: null;

        $symfonyRequest = new SymfonyRequest(
            $_GET,
            $_POST,
            [],
            $_COOKIE,
            $_FILES,
            $_SERVER,
            $content
        );

        if (0 === strpos($symfonyRequest->headers->get('Content-Type'), 'application/json')) {
            $data = json_decode($request->rawContent(), true);
            $symfonyRequest->request->replace(is_array($data) ? $data : []);
        }

        return $symfonyRequest;
    }
}
