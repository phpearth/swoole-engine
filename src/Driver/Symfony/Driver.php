<?php

namespace PhpEarth\Swoole\Driver\Symfony;

use PhpEarth\Swoole\Driver\Symfony\Request;
use PhpEarth\Swoole\Accessor;
use Symfony\Component\Debug\Debug;

use PhpEarth\Swoole\Driver\Symfony\SessionStorage;

/**
 * Driver for running Symfony with Swoole.
 */
class Driver
{
    public $kernel;
    public $symfonyRequest;
    public $symfonyResponse;

    private $swooleRequest;
    private $swooleResponse;

    /**
     * Boot Symfony Application.
     *
     * @param  string $env    Application environment
     * @param  bool   $debug  Switches debug mode on/off
     */
    public function boot($env = 'dev', $debug = true)
    {
        $loader = require __DIR__.'/../../../../../../vendor/autoload.php';

        if ($debug) {
            Debug::enable();
        }

        if (class_exists('\AppKernel')) {
            // Previous Symfony
            $this->kernel = new \AppKernel($env, $debug);
        } else {
            // Symfony Flex
            $this->kernel = new \App\Kernel($env, $debug);
        }
    }

    /**
     * Set Swoole request.
     *
     * @param \swoole_http_request $request
     */
    public function setSwooleRequest(\swoole_http_request $request)
    {
        $this->swooleRequest = $request;
    }

    /**
     * Set Swoole response.
     *
     * @param \swoole_http_response $response
     */
    public function setSwooleResponse(\swoole_http_response $response)
    {
        $this->swooleResponse = $response;
    }

    /**
     * Happens before each request. We need to change session storage service in
     * the middle of Kernel booting process.
     *
     * @return void
     */
    public function preHandle()
    {
        // Reset Kernel startTime, so Symfony can correctly calculate the execution time
        Accessor::set($this->kernel, 'startTime', microtime(true));

        $this->reloadSession();

        Accessor::call(function() {
            $this->initializeBundles();

            $this->initializeContainer();
        }, $this->kernel);

        if ($this->kernel->getContainer()->has('session')) {
            // Inject custom SessionStorage of Symfony Driver
            $nativeStorage = new SessionStorage(
                $this->kernel->getContainer()->getParameter('session.storage.options'),
                $this->kernel->getContainer()->has('session.handler') ? $this->kernel->getContainer()->get('session.handler'): null,
                $this->kernel->getContainer()->get('session.storage')->getMetadataBag()
            );
            $nativeStorage->swooleResponse = $this->swooleResponse;
            $this->kernel->getContainer()->set('session.storage.native', $nativeStorage);
        }

        Accessor::call(function() {
            foreach ($this->getBundles() as $bundle) {
                $bundle->setContainer($this->container);
                $bundle->boot();
            }
            $this->booted = true;
        }, $this->kernel);
    }

    /**
     * Happens after each request.
     *
     * @return void
     */
    public function postHandle()
    {
        // Close database connection.
        if ($this->kernel->getContainer()->has('doctrine.orm.entity_manager')) {
            $this->kernel->getContainer()->get('doctrine.orm.entity_manager')->clear();
            $this->kernel->getContainer()->get('doctrine.orm.entity_manager')->close();
            $this->kernel->getContainer()->get('doctrine.orm.entity_manager')->getConnection()->close();
        }

        $this->kernel->terminate($this->symfonyRequest, $this->symfonyResponse);
    }

    /**
     * Transform Symfony request and response to Swoole compatible response.
     *
     * @return void
     */
    public function handle()
    {
        $rq = new Request();
        $this->symfonyRequest = $rq->createSymfonyRequest($this->swooleRequest);
        $this->symfonyResponse = $this->kernel->handle($this->symfonyRequest);

        // Manually create PHP session cookie. When running Swoole, PHP session_start()
        // function cannot set PHP session cookie since there is no traditional
        // header outputting.
        if (!isset($this->swooleRequest->cookie[session_name()]) &&
            $this->symfonyRequest->hasSession()
        ) {
            $params = session_get_cookie_params();
            $this->swooleResponse->rawcookie(
                $this->symfonyRequest->getSession()->getName(),
                $this->symfonyRequest->getSession()->getId(),
                $params['lifetime'] ? time() + $params['lifetime'] : null,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        // HTTP status code for response
        $this->swooleResponse->status($this->symfonyResponse->getStatusCode());

        // Cookies
        foreach ($this->symfonyResponse->headers->getCookies() as $cookie) {
            $this->swooleResponse->rawcookie(
                $cookie->getName(),
                urlencode($cookie->getValue()),
                $cookie->getExpiresTime(),
                $cookie->getPath(),
                $cookie->getDomain(),
                $cookie->isSecure(),
                $cookie->isHttpOnly()
            );
        }

        // Headers
        foreach ($this->symfonyResponse->headers->allPreserveCase() as $name => $values) {
            //$name = implode('-', array_map('ucfirst', explode('-', $name)));
            foreach ($values as $value) {
                $this->swooleResponse->header($name, $value);
            }
        }

        $this->swooleResponse->end($this->symfonyResponse->getContent());
    }

    /**
     * Fix for managing sessions with Swoole. On each request session_id needs to be
     * regenerated, because we're running PHP script in CLI and listening for requests
     * concurrently.
     *
     * @return void
     */
    private function reloadSession()
    {
        if (isset($this->swooleRequest->cookie[session_name()])) {
            session_id($this->swooleRequest->cookie[session_name()]);
        } else {
            if (session_id()) {
                session_id(\bin2hex(\random_bytes(32)));
            }

            // Empty global session array otherwise it is filled with values from
            // previous session.
            $_SESSION = [];
        }
    }
}
