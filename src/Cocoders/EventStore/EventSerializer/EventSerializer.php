<?php declare(strict_types=1);

namespace Cocoders\EventStore\EventSerializer;

use Cocoders\EventStore\Event;

interface EventSerializer
{
    public function serialize(Event $event): string;
    public function deserialize(string $eventRepresentation, string $type): Event;
}

