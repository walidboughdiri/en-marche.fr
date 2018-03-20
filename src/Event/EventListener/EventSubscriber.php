<?php

namespace AppBundle\Event\EventListener;

use AppBundle\Event\EventBaseEvent;
use AppBundle\Events;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EventSubscriber implements EventSubscriberInterface
{
    private $producer;
    private $serializer;

    public function __construct(ProducerInterface $producer, SerializerInterface $serializer)
    {
        $this->producer = $producer;
        $this->serializer = $serializer;
    }

    public function publishEventCreated(EventBaseEvent $event): void
    {
        $this->producer->publish($this->serialize($event), Events::EVENT_CREATED);
    }

    public function publishEventUpdated(EventBaseEvent $event): void
    {
        $this->producer->publish($this->serialize($event), Events::EVENT_UPDATED);
    }

    public function publishEventDeleted(EventBaseEvent $event): void
    {
        $body = json_encode(['uuid' => $event->getEvent()->getUuid()->toString()]);

        $this->producer->publish($body, Events::EVENT_DELETED);
    }

    public function serialize(EventBaseEvent $event): string
    {
        return $this->serializer->serialize(
            $event->getEvent(),
            'json',
            SerializationContext::create()->setGroups(['public'])
        );
    }

    public static function getSubscribedEvents()
    {
        return [
            // Api Synchronization should be done after all others subscribers so we put the lowest priority
            Events::EVENT_CREATED => [['publishEventCreated', -255]],
            Events::EVENT_UPDATED => [['publishEventUpdated', -255]],
            Events::EVENT_CANCELLED => [['publishEventUpdated', -255]],
            Events::EVENT_DELETED => [['publishEventDeleted', -255]],
        ];
    }
}
