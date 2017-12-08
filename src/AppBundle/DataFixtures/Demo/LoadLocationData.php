<?php

namespace AppBundle\DataFixtures\Demo;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\CommonException;
use AppBundle\Entity\Asset\Location;

class LoadLocationData extends AbstractFixture implements OrderedFixtureInterface
{

    public function load( ObjectManager $manager )
    {

        $shop = $manager->getRepository( 'AppBundle\Entity\Asset\LocationType' )->findOneByName( 'Shop' );
        if( empty( $shop ) )
        {
            throw new CommonException( "The shop location type hasn't been defined (load it before running this)" );
        }

        $location = new Location();
        $location->setType( $shop );

        $manager->persist( $location );

        $locationType = $manager->getRepository( 'AppBundle\Entity\Asset\LocationType' )->findOneByName( 'Venue' );
        if( empty( $locationType ) )
        {
            throw new CommonException( "The venue location type hasn't been defined (load it before running this)" );
        }

        $venues = $manager->getRepository( 'AppBundle\Entity\Venue\Venue' )->findAll();
        if( empty( $venues ) )
        {
            throw new CommonException( "There are no venues defined (load them before running this)" );
        }
        $venueCount = count( $venues ) - 1;

        $location = new Location();
        $location->setEntity( $venues[rand( 0, $venueCount )]->getId() );
        $location->setType( $locationType );

        $manager->persist( $location );

        $manager->flush();
    }

    public function getOrder()
    {
        return 600;
    }

}
