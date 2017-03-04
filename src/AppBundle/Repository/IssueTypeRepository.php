<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Asset\IssueType;

/**
 * IssueTypeRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class IssueTypeRepository extends \Doctrine\ORM\EntityRepository
{

    public function findAll()
    {
        return $this->getEntityManager()
                        ->createQuery(
                                "SELECT i FROM AppBundle\Entity\Asset\IssueType i ORDER BY i.type ASC"
                        )
                        ->getResult();
    }

}
