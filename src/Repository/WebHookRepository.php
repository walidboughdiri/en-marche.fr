<?php

namespace AppBundle\Repository;

use AppBundle\Entity\WebHook\WebHook;
use AppBundle\WebHook\Event;
use Doctrine\ORM\EntityRepository;

class WebHookRepository extends EntityRepository
{
    public function findOneByEvent(Event $event): ?WebHook
    {
        return $this->findOneBy(['event' => $event->getValue()]);
    }

    public function save(WebHook $webHook): void
    {
        $this->_em->persist($webHook);
        $this->_em->flush($webHook);
    }
}