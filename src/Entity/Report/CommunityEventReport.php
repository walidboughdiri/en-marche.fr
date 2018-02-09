<?php

namespace AppBundle\Entity\Report;

use AppBundle\Entity\Event;
use Doctrine\ORM\Mapping as ORM;
use Algolia\AlgoliaSearchBundle\Mapping\Annotation as Algolia;

/**
 * @ORM\Entity
 *
 * @Algolia\Index(autoIndex=false)
 */
class CommunityEventReport extends Report
{
    /**
     * @var Event
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Event")
     * @ORM\JoinColumn(name="community_event_id")
     */
    protected $subject;
}
