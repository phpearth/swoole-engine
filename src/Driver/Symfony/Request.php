<?php

namespace PhpEarth\Swoole\Driver\Symfony;

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class Request
{
    /**
     * Creates Symfony request from Swoole request. PHP superglobals must get set
     * here.
     *
     * @param  \swoole_http_request $request
     * @return Request
     */
    public function createSymfonyRequest(\swoole_http_request $request)
    {
        // Create $_SERVER for traditional PHP applications
        $_SERVER = array_change_key_case($request->server, CASE_UPPER);

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
