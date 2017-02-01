<?php

namespace Evaneos\BurrowBundle;

use Burrow\Driver;
use Burrow\QueueConsumer;
use Burrow\Handler\HandlerBuilder;
use Evaneos\Daemon\Worker;
use Burrow\Daemon\QueueHandlingDaemon;

class WorkerFactory
{
    /**
     * Build a worker.
     *
     * @param  Driver        $driver
     * @param  QueueConsumer $consumer
     * @param  string        $queue
     *
     * @return Worker
     */
    public static function build(Driver $driver, QueueConsumer $consumer, $queue)
    {
        $handlerBuilder = new HandlerBuilder($driver);
        $handler = $handlerBuilder->async()->build($consumer);
        $daemon = new QueueHandlingDaemon($driver, $handler, $queue);

        return new Worker($daemon);
    }
}