<?php

namespace PhpEarth\Swoole\Driver\Symfony;

use PhpEarth\Swoole\Driver\Symfony\Request;
use PhpEarth\Swoole\Accessor;
use Symfony\Component\Debug\Debug;

/**
 * Driver for running Symfony with Swoole.
 */
class Driver
{
    public $kernel;
    public $symfonyRequest;
    public $symfonyResponse;

    /**
     * Boot Symfony Application.
     *
     * @param  string $env    Application environment
     * @param  bool   $debug  Switches debug mode on/off
     */
    public function boot($env = 'dev', $debug = true)
    {
        $loader = require __DIR__.'/../../../../../../app/autoload.php';

        if ($debug) {
            Debug::enable();
        }

        $this->kernel = new \AppKernel($env, $debug);
    }

    /**
     * Happens before each request.
     *
     * @return void
     */
    public function preHandle()
    {
        // Reset Kernel startTime, so Symfony can correctly calculate the execution time
        Accessor::set($this->kernel, 'startTime', microtime(true));

        $this->kernel->shutdown();
        $this->kernel->boot();
    }

    /**
     * Happens after each request.
     *
     * @return void
     */
    public function postHandle()
    {
        $this->kernel->terminate($this->symfonyRequest, $this->symfonyResponse);
    }

    /**
     * Transform Symfony request and response to Swoole response.
     *
     * @param  \swoole_http_request  $request  Swoole request
     * @param  \swoole_http_response $response Swoole response
     * @return void
     */
    public function handle(\swoole_http_request $request, \swoole_http_response $response)
    {
        $rq = new Request();
        $this->symfonyRequest = $rq->createSymfonyRequest($request);
        $this->symfonyResponse = $this->kernel->handle($this->symfonyRequest);

        foreach ($this->symfonyResponse->headers->getCookies() as $cookie) {
            $response->header('Set-Cookie', $cookie);
        }

        foreach ($this->symfonyResponse->headers as $name => $values) {
            $name = implode('-', array_map('ucfirst', explode('-', $name)));
            foreach ($values as $value) {
                $response->header($name, $value);
            }
        }

        $response->end($this->symfonyResponse->getContent());

        return $response;
    }
}
