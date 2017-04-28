<?php

namespace PhpEarth\Swoole\Driver\Symfony;

use Symfony\Component\HttpFoundation\Request as BaseRequest;

class Request
{
    /**
     * Creates Symfony request from Swoole request
     *
     * @param  \swoole_http_request $request
     * @return Request
     */
    public function createSymfonyRequest(\swoole_http_request $request) {
        $_SERVER = isset($request->server) ? array_change_key_case($request->server, CASE_UPPER) : [];

        if (isset($request->header)) {
            $headers = [];
            foreach ($request->header as $k => $v) {
                $k = str_replace('-', '_', $k);
                $headers['http_' . $k] = $v;
            }
            $_SERVER += array_change_key_case($headers, CASE_UPPER);
        }

        $_GET = isset($request->get) ? $request->get : [];
        $_POST = isset($request->post) ? $request->post : [];
        $_COOKIE = isset($request->cookie) ? $request->cookie : [];
        $files = $request->files ?? [];

        $symfonyRequest = new BaseRequest(
            $_GET,
            $_POST,
            [],
            $_COOKIE,
            $files,
            $_SERVER
        );
        if (0 === strpos($symfonyRequest->headers->get('Content-Type'), 'application/json')) {
            $data = json_decode($swRequest->rawContent(), true);
            $symfonyRequest->request->replace(is_array($data) ? $data : array());
        }

        return $symfonyRequest;
    }
}
