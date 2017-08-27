<?php

namespace AppBundle\DataFixtures\Demo;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadUserData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{

    /**
     * @var ContainerInterface
     */
    private $container;

    public function setContainer( ContainerInterface $container = null )
    {
        $this->container = $container;
    }

    public function load( ObjectManager $manager )
    {
        // Thanks to: https://stackoverflow.com/questions/11811102/creating-an-admin-user-using-datafixtures-and-fosuserbundle
        // Get our userManager, you must implement `ContainerAwareInterface`
        $userManager = $this->container->get( 'fos_user.user_manager' );

        // Create our user and set details
        $adminUser = $userManager->createUser();
        $adminUser->setUsername( 'adminuser' );
        $adminUser->setEmail( 'demo@example.com' );
        $adminUser->addRole( 'ROLE_API' );
        $adminUser->addRole( 'ROLE_SUPER_ADMIN' );
        /* Password must be set and account enabled on the command line */
        $adminUser->setEnabled( false );
        $adminUser->setConfirmationToken( 'whatnot' );

        // Update the user
        $userManager->updateUser( $adminUser, true );
    }

    public function getOrder()
    {
        return 1001;
    }

}
