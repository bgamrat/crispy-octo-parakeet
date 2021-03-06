<?php

Namespace App\Controller\Api\Admin\Client;

use App\Entity\Client\Client;
use App\Util\DStore;
use App\Util\Form as FormUtil;
use App\Util\Log;
use App\Form\Admin\Client\ClientType;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\View\View as FOSRestView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

class ClientsController extends FOSRestController
{

    private $dstore;
    private $log;
    private $formUtil;

    public function __construct( DStore $dstore, Log $log, FormUtil $formUtil )
    {
        $this->dstore = $dstore;
        $this->log = $log;
        $this->formUtil = $formUtil;
    }

    /**
     * @View()
     */
    public function getClientsAction( Request $request )
    {
        $this->denyAccessUnlessGranted( 'ROLE_ADMIN', null, 'Unable to access this page!' );
        $dstore = $this->dstore->gridParams( $request, 'name' );

        $em = $this->getDoctrine()->getManager();
        if( $this->isGranted( 'ROLE_SUPER_ADMIN' ) )
        {
            $em->getFilters()->disable( 'softdeleteable' );
        }
        $queryBuilder = $em->createQueryBuilder()->select( ['c'] )
                ->from( 'App\Entity\Client\Client', 'c' )
                ->orderBy( 'c.' . $dstore['sort-field'], $dstore['sort-direction'] );
        $limit = 0;
        if( $dstore['limit'] !== null )
        {
            $limit = $dstore['limit'];
            $queryBuilder->setMaxResults( $limit );
        }
        $offset = 0;
        if( $dstore['offset'] !== null )
        {
            $offset = $dstore['offset'];
            $queryBuilder->setFirstResult( $offset );
        }
        if( $dstore['filter'] !== null )
        {
            switch( $dstore['filter'][DStore::OP] )
            {
                case DStore::LIKE:
                    $queryBuilder->where(
                            $queryBuilder->expr()->like( 'LOWER(c.name)', '?1' )
                    );
                    break;
                case DStore::GT:
                    $queryBuilder->where(
                            $queryBuilder->expr()->gt( 'LOWER(c.name)', '?1' )
                    );
            }
            $queryBuilder->setParameter( 1, strtolower( $dstore['filter'][DStore::VALUE] ) );
        }
        $query = $queryBuilder->getQuery();
        $clientCollection = $query->getResult();
        $data = [];
        foreach( $clientCollection as $c )
        {
            $item = [
                'id' => $c->getId(),
                'name' => $c->getName(),
                'contracts' => $c->getContracts( false ),
                'active' => $c->isActive(),
            ];
            if( $this->isGranted( 'ROLE_SUPER_ADMIN' ) )
            {
                $item['deleted_at'] = $c->getDeletedAt();
            }
            $data[] = $item;
        }
        $count = $em->getRepository( 'App\Entity\Client\Client' )->count([]);
        $view = FOSRestView::create();
        $view->setData( $data );
        $view->setHeader( 'Content-Range', 'items ' . $offset . '-' . ($offset + $limit) . '/' . $count );
        $handler = $this->get( 'fos_rest.view_handler' );
        return $handler->handle( $view );
    }

    /**
     * @View()
     */
    public function getClientAction( $id )
    {
        $this->denyAccessUnlessGranted( 'ROLE_ADMIN', null, 'Unable to access this page!' );
        $em = $this->getDoctrine()->getManager();
        if( $this->isGranted( 'ROLE_SUPER_ADMIN' ) )
        {
            $em->getFilters()->disable( 'softdeleteable' );
        }
        $client = $this->getDoctrine()
                        ->getRepository( 'App\Entity\Client\Client' )->find( $id );
        if( $client !== null )
        {
            $data = [
                'id' => $client->getId(),
                'name' => $client->getName(),
                'active' => $client->isActive(),
                'comment' => $client->getComment(),
                'contacts' => $client->getContacts( false ),
                'contracts' => $client->getContracts( false ),
            ];
            $formUtil = $this->formUtil;
            $formUtil->saveDataTimestamp( 'client' . $client->getId(), $client->getUpdatedAt() );
            return $data;
        }
        else
        {
            throw $this->createNotFoundException( 'Not found!' );
        }
    }

    /**
     * @View()
     */
    public function postClientAction( $id, Request $request )
    {
        return $this->putClientAction( $id, $request );
    }

    /**
     * @View()
     */
    public function putClientAction( $id, Request $request )
    {
        $this->denyAccessUnlessGranted( 'ROLE_ADMIN', null, 'Unable to access this page!' );
        $em = $this->getDoctrine()->getManager();
        $response = new Response();
        $data = $request->request->all();
        if( $id === "null" )
        {
            $client = new Client();
        }
        else
        {
            $client = $em->getRepository( 'App\Entity\Client\Client' )->find( $id );
            $formUtil = $this->formUtil;
            if( $formUtil->checkDataTimestamp( 'client' . $client->getId(), $client->getUpdatedAt() ) === false )
            {
                throw new Exception( "data.outdated", 400 );
            }
        }
        $form = $this->createForm( ClientType::class, $client, ['allow_extra_fields' => true] );
        try
        {
            $form->submit( $data );
            if( $form->isValid() )
            {
                $client = $form->getData();
                $em->persist( $client );
                $em->flush();
                $response->setStatusCode( $request->getMethod() === 'POST' ? 201 : 204  );
                $response->headers->set( 'Location', $this->generateUrl(
                                'app_admin_api_client_get_client', array('id' => $client->getId()), true // absolute
                        )
                );
            }
            else
            {
                return $form;
            }
        }
        catch( Exception $e )
        {
            $response->setStatusCode( 400 );
            $response->setContent( json_encode(
                            ['message' => 'errors', 'errors' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTraceAsString()]
            ) );
        }
        return $response;
    }

    /**
     * @View(statusCode=204)
     */
    public function patchClientAction( $id, Request $request )
    {
        $formProcessor = $this->formUtil;
        $data = $formProcessor->getJsonData( $request );
        $repository = $this->getDoctrine()
                ->getRepository( 'App\Entity\Client\Client' );
        $client = $repository->find( $id );
        if( $client !== null )
        {
            if( isset( $data['field'] ) && is_bool( $formProcessor->strToBool( $data['value'] ) ) )
            {
                $value = $formProcessor->strToBool( $data['value'] );
                switch( $data['field'] )
                {
                    case 'active':
                        $client->setActive( $value );
                        break;
                }

                $em->persist( $client );
                $em->flush();
            }
        }
    }

    /**
     * @View(statusCode=204)
     */
    public function deleteClientAction( $id )
    {
        $this->denyAccessUnlessGranted( 'ROLE_ADMIN', null, 'Unable to access this page!' );
        $em = $this->getDoctrine()->getManager();
        $em->getFilters()->enable( 'softdeleteable' );
        $client = $em->getRepository( 'App\Entity\Client\Client' )->find( $id );
        if( $client !== null )
        {
            $em->remove( $client );
            $em->flush();
        }
        else
        {
            throw $this->createNotFoundException( 'Not found!' );
        }
    }

}
