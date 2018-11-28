<?php

Namespace App\Controller\Api;

use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use FOS\RestBundle\Controller\Annotations\View;
use App\Menu\MenuBuilder;
use App\Menu\JsonRenderer;

class MenuStoreController extends FOSRestController
{
    private $menuBuilder;
    private $jsonRenderer;

    public function __construct (MenuBuilder $menuBuilder, JsonRenderer $jsonRenderer ) {
        $this->menuBuilder = $menuBuilder;
        $this->jsonRenderer = $jsonRenderer;
    }

    /**
     * @View()
     * @Route("/api/menu")
     */
    public function getMenuAction( Request $request )
    {
        //$this->denyAccessUnlessGranted( 'ROLE_ADMIN', null, 'Unable to access this page!' );
        $userMenu = $this->menuBuilder->createUserMenu( [] );
        $adminMenu = $this->menuBuilder->createAdminMenu( [] );
        $renderer = $this->jsonRenderer;
        return [ 'user' =>  $renderer->render( $userMenu ) , 'admin' => $renderer->render( $adminMenu )  ];
        //return $this->render( 'common/parts/nav.html.twig' );
    }

}
