<?php

namespace Tests\Cocoders\Dbal\EventStore;

use Cocoders\EventStore\AggregateRootId;
use Ramsey\Uuid\Uuid;

class MyAggreagateRootId implements AggregateRootId
{
    private $uuid;

    public function __construct(Uuid $uuid)
    {
        $this->uuid = $uuid;
    }

    public function __toString(): string
    {
        return (string) $this->uuid;
    }
}

