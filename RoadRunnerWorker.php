<?php

declare(strict_types=1);

namespace Lit\Runner\RoadRunner;

use Lit\Air\Factory;
use Lit\Bolt\BoltApp;
use Lit\Bolt\BoltContainerConfiguration;
use Psr\Container\ContainerInterface;
use Spiral\RoadRunner\PSR7Client;

/**
 * roadrunner worker
 */
class RoadRunnerWorker
{
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

    /**
     * run a bolt app as roadrunner worker
     *
     * @param array $config The application configuration.
     */
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

    protected function loop()
    {
        $reqCount = 0;
        while ($req = $this->psr7->acceptRequest()) {
            try {
                $resp = $this->app->handle($req);

                $this->psr7->respond($resp);
            } catch (\Throwable $e) {
                $this->psr7->getWorker()->error((string)$e);
            }

            $reqCount++;
            if ($this->maxRequest > 0 && $reqCount >= $this->maxRequest) {
                break;
            }
        }
    }
}
