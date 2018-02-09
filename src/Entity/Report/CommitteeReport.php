<?php

namespace AppBundle\Entity\Report;

use AppBundle\Entity\Committee;
use Doctrine\ORM\Mapping as ORM;
use Algolia\AlgoliaSearchBundle\Mapping\Annotation as Algolia;

/**
 * @ORM\Entity
 *
 * @Algolia\Index(autoIndex=false)
 */
class CommitteeReport extends Report
{
    /**
     * @var Committee
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Committee")
     * @ORM\JoinColumn(name="committee_id")
     */
    protected $subject;
}
