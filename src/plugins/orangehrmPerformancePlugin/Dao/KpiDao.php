<?php
/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software; you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation; either
 * version 2 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program;
 * if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor,
 * Boston, MA  02110-1301, USA
 */

namespace OrangeHRM\Performance\Dao;

use OrangeHRM\Core\Dao\BaseDao;
use OrangeHRM\Core\Traits\Service\DateTimeHelperTrait;
use OrangeHRM\Entity\Kpi;
use OrangeHRM\ORM\QueryBuilderWrapper;
use OrangeHRM\Performance\Dto\KpiSearchFilterParams;

class KpiDao extends BaseDao
{
    use DateTimeHelperTrait;

    /**
     * @param Kpi $kpi
     * @return Kpi
     */
    public function saveKpi(Kpi $kpi): Kpi
    {
        $this->persist($kpi);
        return $kpi;
    }

    /**
     * @param int $id
     * @return Kpi|null
     */
    public function getKpiById(int $id): ?Kpi
    {
        $kpi = $this->getRepository(Kpi::class)->findOneBy(['id' => $id, 'deletedAt' => null]);
        if ($kpi instanceof Kpi) {
            return $kpi;
        }
        return null;
    }

    /**
     * @param KpiSearchFilterParams $kpiSearchFilterParams
     * @return Kpi[]
     */
    public function getKpiList(KpiSearchFilterParams $kpiSearchFilterParams): array
    {
        $qb = $this->getKpiQueryBuilderWrapper($kpiSearchFilterParams)->getQueryBuilder();
        return $qb->getQuery()->execute();
    }

    /**
     * @param KpiSearchFilterParams $kpiSearchFilterParams
     * @return int
     */
    public function getKpiCount(KpiSearchFilterParams $kpiSearchFilterParams): int
    {
        $qb = $this->getKpiQueryBuilderWrapper($kpiSearchFilterParams)->getQueryBuilder();
        return $this->getPaginator($qb)->count();
    }

    /**
     * @param KpiSearchFilterParams $kpiSearchFilterParams
     * @return QueryBuilderWrapper
     */
    private function getKpiQueryBuilderWrapper(KpiSearchFilterParams $kpiSearchFilterParams): QueryBuilderWrapper
    {
        $q = $this->createQueryBuilder(Kpi::class, 'kpi');
        $q->leftJoin('kpi.jobTitle', 'jobTitle');
        $q->andWhere($q->expr()->isNull('kpi.deletedAt'));
        $q->andWhere('jobTitle.isDeleted = :jobTitleIsDeleted')
            ->setParameter('jobTitleIsDeleted', false);
        $this->setSortingAndPaginationParams($q, $kpiSearchFilterParams);

        if (!is_null($kpiSearchFilterParams->getJobTitleId())) {
            $q->andWhere('jobTitle.id = :jobTitleId')
                ->setParameter('jobTitleId', $kpiSearchFilterParams->getJobTitleId());
        }

        $q->addOrderBy('kpi.title');

        return $this->getQueryBuilderWrapper($q);
    }

    /**
     * @param int[] $toBeDeletedKpiIds
     * @return int
     */
    public function deleteKpi(array $toBeDeletedKpiIds): int
    {
        $q = $this->createQueryBuilder(Kpi::class, 'kpi');
        $q->update()
            ->set('kpi.deletedAt', ':deletedAt')
            ->setParameter('deletedAt', $this->getDateTimeHelper()->getNow())
            ->where($q->expr()->in('kpi.id', ':ids'))
            ->setParameter('ids', $toBeDeletedKpiIds);
        return $q->getQuery()->execute();
    }
}