<?php

declare(strict_types=1);

namespace Lit\Runner\RoadRunner;

use Lit\Air\Factory;
use Lit\Bolt\BoltApp;
use Lit\Bolt\BoltContainerConfiguration;
use Psr\Container\ContainerInterface;
use Spiral\RoadRunner\PSR7Client;

class RoadRunnerWorker
{
    const EVENT_LOOP_FINAL = 'rr.request.final';
    const EVENT_LOOP_AFTER_RESPOND = 'rr.request.after_respond';
    const EVENT_LOOP_ERROR = 'rr.request.error';

    /**
     * @var PSR7Client
     */
    protected $psr7;

    /**
     * @var BoltApp
     */
    protected $app;
    /**
     * @var int
     */
    protected $maxRequest;

    public function __construct(PSR7Client $psr7, BoltApp $app, int $maxRequest = 0)
    {
        $this->psr7 = $psr7;
        $this->app = $app;
        $this->maxRequest = $maxRequest;
    }

    public static function run($config = [])
    {
        $container = $config instanceof ContainerInterface
            ? $config
            : BoltContainerConfiguration::createContainer($config + RoadRunnerConfiguration::default());

        /**
         * @var static
         */
        $instance = Factory::of($container)->getOrProduce(static::class);
        $instance->loop();
        exit(0);
    }

    public function loop()
    {
        $reqCount = 0;
        while ($req = $this->psr7->acceptRequest()) {
            try {
                $resp = $this->app->handle($req);

                $this->psr7->respond($resp);
                $this->app->getEventsHub()->dispatch(static::EVENT_LOOP_AFTER_RESPOND);
            } catch (\Throwable $e) {
                $this->psr7->getWorker()->error((string)$e);
                $this->app->getEventsHub()->dispatch(static::EVENT_LOOP_ERROR);
            } finally {
                $this->app->getEventsHub()->dispatch(static::EVENT_LOOP_FINAL);
            }

            $reqCount++;
            if ($this->maxRequest > 0 && $reqCount >= $this->maxRequest) {
                break;
            }
        }
    }
}
