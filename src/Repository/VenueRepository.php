<?php

Namespace App\Repository;

/**
 * VenueApp\Repository
 */
class VenueRepository extends \Doctrine\ORM\EntityRepository
{

    public function findByNameLike( $name )
    {
        $name = '%' . str_replace( '*', '%', strtolower( $name ) );
        return $this->getEntityManager()
                        ->createQuery(
                                "SELECT v FROM App\Entity\Venue\Venue v WHERE LOWER(v.name) LIKE :name ORDER BY v.name ASC"
                        )
                        ->setParameter( 'name', $name )
                        ->getResult();
    }

}
