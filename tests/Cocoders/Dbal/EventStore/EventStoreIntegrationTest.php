<?php

namespace Tests\Cocoders\Dbal\EventStore;

use Cocoders\Dbal\EventStore\EventStore;
use Cocoders\Dbal\EventStore\SchemaManager;
use Cocoders\EventStore\EventSerializer\JMS\EventSerializer as JMSEventSerializer;
use Cocoders\EventStore\EventSerializer\Symfony\EventSerializer as SymfonyEventSerializer;
use Cocoders\EventStore\EventStream;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use JMS\Serializer\SerializerBuilder;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

class EventStoreIntegrationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_allows_store_and_find_events_in_mysql_using_jms_serializer_database()
    {
        $connection = $this->getMysqlConnection();
        $schemaManager = new SchemaManager($connection);
        $schemaManager->dropStreamTable(new EventStream\Name(static::class));
        $schemaManager->createStreamTable(new EventStream\Name(static::class));

        $id = new MyAggreagateRootId(Uuid::uuid4());
        $otherId = new MyAggreagateRootId(Uuid::uuid4());

        $events = [
            new MyFakeEvent(
                $id,
                [
                    'myPreciousInformation' => '1',
                    'test' => 'otherTest'
                ]
            ),
            new MyFakeEvent(
                $id,
                [
                    'myPreciousInformation' => '2',
                    'test' => 'otherTest'
                ]
            ),
            new MyFakeEvent(
                $otherId,
                [
                    'myPreciousInformation' => '1',
                    'test' => 'what?'
                ]
            )
        ];

        $eventSerializer = new JMSEventSerializer(
            SerializerBuilder::create()
                ->addMetadataDir(__DIR__.'/jms/mapping', 'Tests\\Cocoders\\Dbal\\EventStore')
                ->build()
        );

        $eventStore = new EventStore($connection, $eventSerializer);
        $eventStore->apply(new EventStream\Name(static::class), $events);

        $unncommitedEvents = $eventStore->findUncommited(new EventStream\Name(static::class))->all();
        $this->assertCount(3, $unncommitedEvents, 'Has all uncommited events before commit');

        $eventStore->commit();

        $unncommitedEvents = $eventStore->findUncommited(new EventStream\Name(static::class))->all();
        $this->assertCount(0, $unncommitedEvents, 'After commit uncommitedEvents are removed');

        $eventStore = new EventStore($connection, $eventSerializer);
        $allEvents = $eventStore->all(new EventStream\Name(static::class))->all();
        $this->assertCount(3, $allEvents, 'Found all events from given stream');

        $aggregateEvents = $eventStore->find(new EventStream\Name(static::class), $id)->all();
        $this->assertCount(2, $aggregateEvents, 'Found all aggregate events from stream');

        $this->assertInstanceOf(MyFakeEvent::class, $aggregateEvents[0], 'Found event is valid type/class');
        $this->assertEquals(
            [
                'myPreciousInformation' => '1',
                'test' => 'otherTest'
            ],
            $aggregateEvents[0]->getData(),
            'Found event has valid data'
        );
    }

    private function getMysqlConnection(): Connection
    {
        $config = new \Doctrine\DBAL\Configuration();
        $connectionParams = array(
            'url' => 'mysql://root:secretRootPassword@mysql/eventStore',
        );

        return DriverManager::getConnection($connectionParams, $config);
    }
}


