<?php declare(strict_types=1);

namespace Cocoders\Dbal\EventStore;

use Cocoders\EventStore\AggregateRootId;
use Cocoders\EventStore\Event;
use Cocoders\EventStore\EventSerializer\EventSerializer;
use Cocoders\EventStore\EventStore as EventStoreInterface;
use Cocoders\EventStore\EventStream;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDOStatement;
use Doctrine\DBAL\Statement;

final class EventStore implements EventStoreInterface
{
    private $connection;
    private $serializer;
    /**
     * @var Event[][]
     */
    private $uncommitedEvents = [];

    public function __construct(
        Connection $connection,
        EventSerializer $serializer
    ) {
        $this->connection = $connection;
        $this->serializer = $serializer;
    }

    public function find(EventStream\Name $name, AggregateRootId $id): EventStream
    {
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select('event', 'event_class')
            ->from(SchemaManager::normalizeToTableName($name))
            ->where('aggregate_id = :id')
            ->setParameter('id', (string) $id)
            ->orderBy('occurred_on', 'ASC')
        ;

        $this->connection->beginTransaction();

        try{
            $stmt = $qb->execute();
            $this->connection->commit();
        } catch(Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }

        $events = $this->hydrateEvents($stmt);

        return new EventStream($name, $events);
    }

    public function findUncommited(EventStream\Name $name): EventStream
    {
        return new EventStream($name, $this->uncommitedEvents[(string) $name] ?? []);
    }

    public function all(EventStream\Name $name): EventStream
    {
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select('event', 'event_class')
            ->from(SchemaManager::normalizeToTableName($name))
            ->orderBy('occurred_on', 'ASC')
        ;
        $stmt = $qb->execute();

        $events = $this->hydrateEvents($stmt);

        return new EventStream($name, $events);
    }

    public function apply(EventStream\Name $name, array $events)
    {
        if (! isset($this->uncommitedEvents[(string) $name])) {
            $this->uncommitedEvents[(string) $name] = $events;
            return;
        }

        if (isset($this->uncommitedEvents[(string) $name])) {
            $this->uncommitedEvents[(string) $name] = array_merge(
                $this->uncommitedEvents[(string) $name],
                $events
            );
        }
    }

    public function commit()
    {
        $this->connection->beginTransaction();
        foreach ($this->uncommitedEvents as $name => $events) {
            $streamName = new EventStream\Name($name);
            foreach ($events as $event) {
                $this->insertEvent($streamName, $event);
            }
        }
        $this->connection->commit();
        $this->uncommitedEvents = [];
    }

    private function insertEvent(EventStream\Name $streamName, Event $event)
    {
        $this->connection->insert(
            SchemaManager::normalizeToTableName($streamName),
            [
                'aggregate_id' => (string) $event->getAggreagateRootId(),
                'name' => $event->getName(),
                'occurred_on' => $event->occurredOn(),
                'event' => $this->serializer->serialize($event),
                'event_class' => get_class($event)
            ],
            [
                \PDO::PARAM_STR,
                \PDO::PARAM_STR,
                'datetime',
                'text',
                \PDO::PARAM_STR,
            ]
        );
    }

    private function hydrateEvents(PDOStatement $stmt)
    {
        return array_map(
            function ($eventArray) {
                return $this->serializer->deserialize($eventArray['event'], $eventArray['event_class']);
            },
            $stmt->fetchAll()
        );
    }
}

