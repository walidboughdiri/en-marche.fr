<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\ReferentTag;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class LoadReferentTagData extends Fixture
{
    public function load(ObjectManager $manager)
    {
        // French department tags
        foreach (\range(1, 98) as $department) {
            $department = \str_pad($department, 2, '0', STR_PAD_LEFT);

            switch ($department) {
                // 2 separate tags for Corsica
                case '20':
                    $this->createReferentTag($manager, '2A');
                    $this->createReferentTag($manager, '2B');

                    break;
                // 1 tag for each Paris district
                case '75':
                    foreach (\range(1, 20) as $district) {
                        $district = \str_pad($district, 2, '0', STR_PAD_LEFT);

                        $this->createReferentTag($manager, "750$district");
                    }

                    break;
                // does not exist
                case '96':
                    break;
                default:
                    $this->createReferentTag($manager, $department);

                    break;
            }
        }

        // Country tags
        $this->createReferentTag($manager, 'CH');
        $this->createReferentTag($manager, 'DE');
        $this->createReferentTag($manager, 'SG');
        $this->createReferentTag($manager, 'US');

        $manager->flush();
    }

    private function createReferentTag(ObjectManager $manager, string $name): void
    {
        $referentTag = new ReferentTag($name);

        $manager->persist($referentTag);

        $this->addReference('referent_tag_'.\strtolower($name), $referentTag);
    }
}
