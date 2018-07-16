<?php

namespace Evaneos\BurrowBundle;

use Burrow\Driver;
use Burrow\Publisher\AsyncPublisher;
use Burrow\Publisher\SerializingPublisher;
use Burrow\Serializer\JsonSerializer;
use Burrow\Serializer\PhpSerializer;

class PublisherFactory
{
    const SERIALIZING_STRATEGY_NONE = 'none';
    const SERIALIZING_STRATEGY_SERIALIZE = 'serialize';
    const SERIALIZING_STRATEGY_JSON = 'json';
    const DEFAULT_SERIALIZING_STRATEGY = self::SERIALIZING_STRATEGY_JSON;

    public static $SERIALIZING_STRATEGIES = [self::SERIALIZING_STRATEGY_NONE, self::SERIALIZING_STRATEGY_SERIALIZE, self::SERIALIZING_STRATEGY_JSON];

    /**
     * @param Driver $driver
     * @param string $exchangeName
     * @param string $serializingStrategy
     * @return AsyncPublisher|SerializingPublisher
     */
    public static function buildAsync(Driver $driver, $exchangeName, $serializingStrategy)
    {
        $publisher = new AsyncPublisher($driver, $exchangeName);

        if (self::SERIALIZING_STRATEGY_NONE === $serializingStrategy) {
            return $publisher;
        } elseif (self::SERIALIZING_STRATEGY_JSON === $serializingStrategy) {
            return new SerializingPublisher($publisher, new JsonSerializer());
        } else {
            return new SerializingPublisher($publisher, new PhpSerializer());
        }
    }
}
