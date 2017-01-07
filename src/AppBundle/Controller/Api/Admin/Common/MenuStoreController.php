<?php

namespace AppBundle\Controller\Api\Admin\Common;

use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations\View;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use FOS\RestBundle\Controller\Annotations\Get;

class MenuStoreController extends FOSRestController
{

    private $entityMenus;

    public function __construct( Array $entityMenus )
    {
        $this->entityMenus = $entityMenus;
    }


    /**
     * @View()
     * @Get("/adminmenus")
     * @Get("/adminmenus/", name="_alt")
     * @Get("/adminmenus/?parent={parent}", name="_parent", defaults={"parent" = "admin"})
     * @Get("/adminmenus/{id}", name="_id", defaults={"id" = "admin"})
     *
     */
    public function getAdminmenuAction( Request $request )
    {
        $this->denyAccessUnlessGranted( 'ROLE_ADMIN', null, 'Unable to access this page!' );
        $adminMenu = $this->get( 'app.menu_builder' )->createAdminMenu( [] );
        $renderer = $this->get( 'app.menu_renderer' );
        $menu = [];
        $id = $request->get( 'id' );
        if( $id !== null )
        {
            if( !in_array( $id, $this->entityMenus ) && preg_match( '/\-\d+$/', $id ) === 0 )
            {
                $menu = $renderer->render( $adminMenu, ['depth' => 1] );
                $menu = $menu[$id];
            }
            else
            {
                $dynamicId = trim( ucfirst( preg_replace( '/^([a-z]+).*$/', '$1', $id ) ), 's' );
                $menuMethod = 'get' . $dynamicId . 'Menu';
                if( method_exists( $this, $menuMethod ) )
                {
                    $menu = $this->{$menuMethod}( $adminMenu, $renderer, $id );
                }
            }
        }
        $parent = $request->get( 'parent' );
        if( $parent !== null )
        {
            foreach( $adminMenu as $name => $children )
            {
                if( $name === $parent )
                {
                    $menu['id'] = $parent;
                    $menu['name'] = $name;
                    $menu['children'] = $renderer->render( $children, ['depth' => 1], 'json' );

                    break;
                }
            }
        }
        if( isset( $menu['children'] ) )
        {
            foreach( $menu['children'] as $c => $child )
            {
                if( in_array( $child['id'], $this->entityMenus ) )
                {
                    $menu['children'][$c]['has_children'] = true;
                }
            }
        }
        return $menu;
    }
    
    function getManufacturerMenu( $adminMenu, $renderer, $id )
    {
        $limit = 2500; // TODO: Change to deliver first letters if there are too many manfacturers
        $em = $this->getDoctrine()->getManager();
        $base = explode( '-', $id );
        switch( $base[0] )
        {
            case 'manufacturers':
                $queryBuilder = $em->createQueryBuilder();
                $queryBuilder
                        ->select( ["CONCAT('manufacturer-',m.id) AS id", 'm.name', "'manufacturer' AS parent", 'COUNT(b.id) AS has_children'] )
                        ->from( 'AppBundle\Entity\Asset\Manufacturer', 'm' )
                        ->leftJoin( 'm.brands', 'b' )
                        ->orderBy( 'm.name' )
                        ->groupBy( 'm.id' )
                        ->setFirstResult( 0 )
                        ->setMaxResults( $limit );
                $menu = $renderer->render( $adminMenu['admin']['admin-assets']['manufacturers'] );
                $children = $queryBuilder->getQuery()->getResult();
                $l = count( $children );
                if( $l < $limit )
                {
                    for( $i = 0; $i < $l; $i++ )
                    {
                        $children[$i]['uri'] = $this->generateUrl(
                                'app_admin_asset_manufacturer_index', ['name' => $children[$i]['name']], true ); // absolute
                    }
                }
                $menu['children'] = $children;
                break;
            case 'manufacturer':
                $manufacturer = $em->getRepository( 'AppBundle\Entity\Asset\Manufacturer' )->find( $base[1] );
                $brands = $manufacturer->getBrands();
                $children = [];
                foreach( $brands as $b )
                {
                    $children[] = [
                        'id' => $b->getId(),
                        'name' => $b->getName(),
                        'parent' => $id,
                        'uri' => $this->generateUrl(
                                'app_admin_asset_manufacturer_getmanufacturerbrand', ['mname' => $manufacturer->getName(), 'bname' => $b->getName()], true ), // absolute
                        'has_children' => false,
                        'children' => null];
                }
                $menu['children'] = $children;
                break;
        }
        return $menu;
    }

    function getTrailerMenu( $adminMenu, $renderer, $id )
    {
        $limit = 2500; // TODO: Change to deliver first letters if there are too many manfacturers
        $em = $this->getDoctrine()->getManager();
        $queryBuilder = $em->createQueryBuilder();
        $queryBuilder
                ->select( ["CONCAT('trailer-',t.id) AS id", 't.name', "'trailer' AS parent"] )
                ->from( 'AppBundle\Entity\Asset\Trailer', 't' )
                ->orderBy( 't.name' )
                ->setFirstResult( 0 )
                ->setMaxResults( $limit );
        $menu = $renderer->render( $adminMenu['admin']['admin-assets']['trailers'] );
        $children = $queryBuilder->getQuery()->getResult();
        $l = count( $children );
        if( $l < $limit )
        {
            for( $i = 0; $i < $l; $i++ )
            {
                $children[$i]['has_children'] = false;
                $children[$i]['children'] = null;
                $children[$i]['uri'] = $this->generateUrl(
                        'app_admin_asset_trailer_view', ['name' => $children[$i]['name']], true ); // absolute
            }
        }
        $menu['children'] = $children;
        return $menu;
    }

    /**
     * @View()
     */
    public function getVendorsAction( Request $request )
    {
        $this->denyAccessUnlessGranted( 'ROLE_ADMIN', null, 'Unable to access this page!' );

        $name = $request->get( 'name' );
        if( !empty( $name ) )
        {
            $name = '%' . str_replace( '*', '%', $name );

            $em = $this->getDoctrine()->getManager();

            $queryBuilder = $em->createQueryBuilder()->select( ['v.id', "v.name"] )
                    ->from( 'AppBundle\Entity\Asset\Vendor', 'v' )
                    ->where( "LOWER(v.name) LIKE :vendor_name" )
                    ->setParameter( 'vendor_name', strtolower($name) );

            $data = $queryBuilder->getQuery()->getResult();
        }
        else
        {
            $data = null;
        }
        return $data;
    }

}