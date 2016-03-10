<?php

namespace Tests\Cocoders\Dbal\EventStore;

use Cocoders\EventStore\AggregateRootId;
use Cocoders\EventStore\Event;
use DateTime;
use DateTimeInterface;
use Ramsey\Uuid\Uuid;

class MyFakeEvent implements Event
{
    private $id;
    private $occurredOn;
    private $data;

    public function __construct($id, $data = [])
    {
        $this->id = (string) $id;
        $this->occurredOn = new DateTime();
        $this->data = $data;
    }

    public function getAggreagateRootId(): AggregateRootId
    {
        return new MyAggreagateRootId(Uuid::fromString($this->id));
    }

    public function getName(): string
    {
        return 'MyFakeEvent';
    }

    public function occurredOn(): DateTimeInterface
    {
        return $this->occurredOn;
    }

    public function getData()
    {
        return $this->data;
    }
}

