<?php

namespace Tests;

use Burrow\QueueConsumer;

class SomeConsumer implements QueueConsumer
{
    public function consume($message, array $headers = [])
    {
        // Do nothing
    }
}