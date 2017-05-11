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
        $this->setServer($request);

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

    /**
     * Create $_SERVER superglobal for traditional PHP applications. By default
     * Swoole request contains headers with lower case keys and dash separator
     * instead of underscores and upper case letters which PHP expects in the
     * $_SERVER superglobal. For example:
     * - host: localhost:9501
     * - connection: keep-alive
     * - accept-language: en-US,en;q=0.8,sl;q=0.6
     *
     * @param \swoole_http_request $request
     */
    public function setServer($request)
    {
        $headers = [];

        // For just in case security we'll whitelist the expected headers. No more,
        // should be added to $_SERVER.
        $whitelist = [
            'HTTP_HOST',
            'HTTP_CONNECTION',
            'HTTP_USER_AGENT',
            'HTTP_ACCEPT',
            'HTTP_ACCEPT_ENCODING',
            'HTTP_ACCEPT_LANGUAGE',
            'HTTP_REFERER',
            'HTTP_ACCEPT_CHARSET'
        ];

        foreach ($request->header as $key => $value) {
            $headerKey = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
            if (in_array($headerKey, $whitelist)) {
                $headers[$headerKey] = $value;
            }
        }

        // Make swoole's server's keys uppercased and merge them into the $_SERVER superglobal
        $_SERVER = array_change_key_case(array_merge($request->server, $headers), CASE_UPPER);
    }
}
