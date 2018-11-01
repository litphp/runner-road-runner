<?php

// @codeCoverageIgnoreStart

use Lit\Air\Configurator as C;
use Lit\Bolt\BoltAbstractAction;
use Lit\Bolt\BoltApp;
use Lit\Runner\RoadRunner\RoadRunnerWorker;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

is_readable(__DIR__ . '/../vendor/autoload.php')
    ? require(__DIR__ . '/../vendor/autoload.php')
    : require(__DIR__ . '/../../../../../vendor/autoload.php');

class HelloAction extends BoltAbstractAction
{
    protected function main(): ResponseInterface
    {
        return $this->json()->render([
            'hello' => 'world',
            'method' => $this->request->getMethod(),
            'uri' => $this->request->getUri()->__toString(),
        ]);
    }
}

RoadRunnerWorker::run([
//    C::join(RoadRunnerWorker::class, 'maxRequest') => 1000,
    BoltApp::class => C::provideParameter([
        RequestHandlerInterface::class => C::produce(HelloAction::class),
    ])
]);

// @codeCoverageIgnoreEnd
