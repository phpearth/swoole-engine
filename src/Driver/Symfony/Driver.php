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
    private $kernel;
    private $symfonyRequest;
    private $symfonyResponse;

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
        //resets Kernels startTime, so Symfony can correctly calculate the execution time
        Accessor::set($this->kernel, 'startTime', microtime(true));

        Accessor::call(function() {
            // init bundles
            $this->initializeBundles();

            // init container
            $this->initializeContainer();
        }, $this->kernel);
    }

    /**
     * Happens after each request.
     *
     * @return void
     */
    public function postHandle()
    {
        // Reset the stopwatch in debug toolbar in case it is used (development environment)
        if ($this->kernel->getContainer()->has('debug.stopwatch')) {
            $this->kernel->getContainer()->get('debug.stopwatch')->__construct();
        }

        // Resets profiler so the debug toolbar is visible in other requests as well.
        if ($this->kernel->getContainer()->has('profiler')) {
            $this->kernel->getContainer()->get('profiler')->enable();

            // PropelLogger
            if ($this->kernel->getContainer()->has('propel.logger')) {
                $propelLogger = $this->kernel->getContainer()->get('propel.logger');
                Accessor::set($propelLogger, 'queries', []);
            }

            // Doctrine\Bundle\DoctrineBundle\DataCollector\DoctrineDataCollector
            if ($this->kernel->getContainer()->get('profiler')->has('db')) {
                Accessor::call(function () {
                    //$logger: \Doctrine\DBAL\Logging\DebugStack
                    foreach ($this->loggers as $logger){
                        Accessor::set($logger, 'queries', []);
                    }
                }, $this->kernel->getContainer()->get('profiler')->get('db'), null, 'Symfony\Bridge\Doctrine\DataCollector\DoctrineDataCollector');
            }

            // EventDataCollector
            if ($this->kernel->getContainer()->get('profiler')->has('events')) {
                Accessor::set($this->kernel->getContainer()->get('profiler')->get('events'), 'data', [
                    'called_listeners' => [],
                    'not_called_listeners' => [],
                ]);
            }

            // TwigDataCollector
            if ($this->kernel->getContainer()->get('profiler')->has('twig')) {
                Accessor::call(function () {
                    Accessor::set($this->profile, 'profiles', []);
                }, $this->kernel->getContainer()->get('profiler')->get('twig'));
            }

            // Logger
            if ($this->kernel->getContainer()->has('logger')) {
                $logger = $this->kernel->getContainer()->get('logger');
                Accessor::call(function () {
                    if ($debugLogger = $this->getDebugLogger()) {
                        //DebugLogger
                        Accessor::set($debugLogger, 'records', []);
                    }
                }, $this->kernel->getContainer()->get('logger'));
            }

            // Symfony\Bundle\SwiftmailerBundle\DataCollector\MessageDataCollector
            if ($this->kernel->getContainer()->hasParameter('swiftmailer.mailers')) {
                $mailers = $this->kernel->getContainer()->getParameter('swiftmailer.mailers');
                foreach ($mailers as $name => $mailer) {
                    $loggerName = sprintf('swiftmailer.mailer.%s.plugin.messagelogger', $name);
                    if ($this->kernel->getContainer()->has($loggerName)) {
                        $logger = $this->kernel->getContainer()->get($loggerName);
                        $logger->clear();
                    }
                }
            }

            // Symfony\Bridge\Swiftmailer\DataCollector\MessageDataCollector
            if ($this->kernel->getContainer()->has('swiftmailer.plugin.messagelogger')) {
                $logger = $this->kernel->getContainer()->get('swiftmailer.plugin.messagelogger');
                $logger->clear();
            }

            $this->kernel->terminate($this->symfonyRequest, $this->symfonyResponse);
        }
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
