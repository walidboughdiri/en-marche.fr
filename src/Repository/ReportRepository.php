<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Adherent;
use AppBundle\Entity\Report\Report;
use AppBundle\Report\ReportType;
use Doctrine\ORM\EntityRepository;

class ReportRepository extends EntityRepository
{
    /**
     * @return int[]
     */
    public function findIdsByNameForClass(string $class, string $name): array
    {
        if (!is_subclass_of($class, Report::class)) {
            throw new \InvalidArgumentException(\sprintf('The class %s should extend %s.', $class, Report::class));
        }

        $ids = $this->_em->createQueryBuilder()
            ->from($class, 'report')
            ->select('report.id')
            ->join('report.subject', 'subject')
            ->andWhere('subject.name LIKE :name')
            ->setParameter('name', sprintf('%%%s%%', $name))
            ->getQuery()
            ->getScalarResult()
        ;

        return array_column($ids, 'id');
    }

    /**
     * @return int[]
     */
    public function findIdsByNameForAll(string $name): array
    {
        $ids = [];

        foreach (ReportType::LIST as $class) {
            $ids = array_merge($ids, $this->findIdsByNameForClass($class, $name));
        }

        sort($ids);

        return $ids;
    }

    public function anonymizeAuthorReports(Adherent $adherent): void
    {
        $this->createQueryBuilder('r')
            ->update()
            ->set('r.author', ':new_value')
            ->setParameter('new_value', null)
            ->where('r.author = :author')
            ->setParameter('author', $adherent)
            ->getQuery()
            ->execute()
        ;
    }
}
