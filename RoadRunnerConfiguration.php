<?php

declare(strict_types=1);

namespace Lit\Runner\RoadRunner;

use Lit\Air\Configurator as C;
use Lit\Bolt\BoltApp;
use Psr\Http\Message\ResponseFactoryInterface;
use Spiral\Goridge\RelayInterface;
use Spiral\Goridge\StreamRelay;
use Spiral\RoadRunner\PSR7Client;
use Spiral\RoadRunner\Worker;
use Zend\Diactoros\ResponseFactory;

class RoadRunnerConfiguration
{
    public static function default(): array
    {
        return [
            RoadRunnerWorker::class => C::provideParameter([
                PSR7Client::class => C::alias(RoadRunnerWorker::class, 'psr7'),
                BoltApp::class => C::alias(BoltApp::class),
                'maxRequest' => C::alias(RoadRunnerWorker::class, 'maxRequest'),
            ]),
            C::join(RoadRunnerWorker::class, 'psr7') => C::instance(PSR7Client::class, [
                C::alias(PSR7Client::class, 'worker')
            ]),
            C::join(RoadRunnerWorker::class, 'maxRequest') => 0,
            C::join(PSR7Client::class, 'worker') => C::instance(Worker::class, [
                RelayInterface::class => C::alias(Worker::class, 'relay'),
            ]),
            C::join(Worker::class, 'relay') => C::instance(StreamRelay::class, [STDIN, STDOUT]),
            ResponseFactoryInterface::class => C::produce(ResponseFactory::class),
        ];
    }
}
