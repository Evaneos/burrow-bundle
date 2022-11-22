<?php

namespace Evaneos\BurrowBundle;

use Burrow\Daemon\QueueHandlingDaemon;
use Burrow\Driver;
use Burrow\Handler\HandlerBuilder;
use Burrow\QueueConsumer;
use Evaneos\Daemon\Worker;
use Psr\Log\LoggerInterface;

class WorkerFactory
{
    /**
     * Builds a worker.
     *
     * @param  Driver        $driver
     * @param  QueueConsumer $consumer
     * @param  string        $queue
     * @param  bool          $requeueOnFailure
     *
     * @return Worker
     */
    public static function build(
        Driver $driver,
        QueueConsumer $consumer,
        $queue,
        $requeueOnFailure = true,
        LoggerInterface $logger
    )
    {
        $handlerBuilder = new HandlerBuilder($driver);

        if ($requeueOnFailure === false) {
            $handlerBuilder->doNotRequeueOnFailure();
        }

        $handler = $handlerBuilder->async()->log($logger)->build($consumer);
        $daemon = new QueueHandlingDaemon($driver, $handler, $queue);

        return new Worker($daemon);
    }
}
