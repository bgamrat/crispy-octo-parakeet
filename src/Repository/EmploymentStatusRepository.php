<?php

Namespace App\Repository;

/**
 * EmployeeStatusApp\Repository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class EmploymentStatusRepository extends \Doctrine\ORM\EntityRepository
{

    public function findAll()
    {
        return $this->getEntityManager()
                        ->createQuery(
                                "SELECT es FROM App\Entity\Staff\EmploymentStatus es ORDER BY es.name ASC"
                        )
                        ->getResult();
    }

}
