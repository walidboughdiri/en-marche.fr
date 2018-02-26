<?php

namespace AppBundle\Referent;

use AppBundle\Entity\Adherent;
use AppBundle\Repository\ReferentTagRepository;

class ReferentTagManager
{
    private const DEFAULT_LOCAL_CODE = 'undefined';

    private $referentTagRepository;

    public function __construct(ReferentTagRepository $referentTagRepository)
    {
        $this->referentTagRepository = $referentTagRepository;
    }

    public function assignAdherentLocalTag(Adherent $adherent): void
    {
        $code = ManagedAreaUtils::getCodeFromAdherent($adherent);

        if (!$tag = $this->referentTagRepository->findOneByCode($code)) {
            $tag = $this->referentTagRepository->findOneByCode(self::DEFAULT_LOCAL_CODE);
        }

        $adherent->removeReferentTags();
        $adherent->addReferentTag($tag);
    }
}
