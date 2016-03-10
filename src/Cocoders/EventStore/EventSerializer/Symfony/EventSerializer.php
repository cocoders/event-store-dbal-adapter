<?php declare(strict_types=1);

namespace Cocoders\EventStore\EventSerializer\Symfony;

use Cocoders\EventStore\Event;
use Cocoders\EventStore\EventSerializer\EventSerializer as EventSerializerInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class EventSerializer implements EventSerializerInterface
{
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function serialize(Event $event): string
    {
        return $this->serializer->serialize($event, 'json');
    }

    public function deserialize(string $eventRepresentation, string $type): Event
    {
        return $this->serializer->deserialize($eventRepresentation, $type, 'json');
    }
}

