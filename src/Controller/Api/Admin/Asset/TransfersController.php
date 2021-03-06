<?php

Namespace App\Controller\Api\Admin\Asset;

use App\Util\DStore;
use App\Util\Log;
use App\Util\Form as FormUtil;
use App\Entity\Asset\Transfer;
use App\Entity\Asset\Trailer;
use App\Entity\Common\Person;
use App\Entity\Asset\Location;
use App\Form\Admin\Asset\TransferType;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\View\View as FOSRestView;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TransfersController extends FOSRestController
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
    public function getTransfersAction( Request $request )
    {
        $this->denyAccessUnlessGranted( 'ROLE_ADMIN', null, 'Unable to access this page!' );
        $dstore = $this->dstore->gridParams( $request, 'id' );
        switch( $dstore['sort-field'] )
        {
            case 'barcode':
                $sortField = 'bc.barcode';
                break;
            case 'status_text':
                $sortField = 's.name';
                break;
            case 'carrier_text':
                $sortField = 'c.name';
                break;
            default:
                $sortField = 't.' . $dstore['sort-field'];
        }
        $em = $this->getDoctrine()->getManager();
        if( $this->isGranted( 'ROLE_SUPER_ADMIN' ) )
        {
            $em->getFilters()->disable( 'softdeleteable' );
        }
        $columns = ['i'];
        if( $this->isGranted( 'ROLE_SUPER_ADMIN' ) )
        {
            $columns[] = 't.deletedAt AS deleted_at';
        }
        $transferIds = [];
        if( !empty( $dstore['filter'][DStore::VALUE] ) )
        {
            $assetData = $em->getRepository( 'App\Entity\Asset\Asset' )->findByBarcode( $dstore['filter'][DStore::VALUE] );
            if( !empty( $assetData ) )
            {
                $assetIds = [];
                foreach( $assetData as $a )
                {
                    $assetIds[] = $a->getId();
                }
                $queryBuilder = $em->createQueryBuilder()->select( 't.id' )
                        ->from( 'App\Entity\Asset\Transfer', 't' )
                        ->join( 't.items', 'ti' )
                        ->join( 'ti.asset', 'a' );
                $queryBuilder->where( 'a.id IN (:asset_ids)' );
                $queryBuilder->setParameter( 'asset_ids', $assetIds );
                $transferData = $queryBuilder->getQuery()->getResult();
                $transferIds = [];
                foreach( $transferData as $i )
                {
                    $transferIds[] = $i['id'];
                }
            }
        }

        $columns = ['t.id', 't.instructions', 's.name AS status_text', 't.source_location_text', 't.destination_location_text',
           'c.name AS carrier_text', 't.tracking_number', 'c.tracking_url'];
        $queryBuilder = $em->createQueryBuilder()->select( $columns )
                ->from( 'App\Entity\Asset\Transfer', 't' )
                ->join( 't.status', 's' )
                ->leftJoin( 't.carrier', 'c' )
                ->orderBy( $sortField, $dstore['sort-direction'] );

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
                            $queryBuilder->expr()->orX(
                                    $queryBuilder->expr()->orX(
                                            $queryBuilder->expr()->like( 'LOWER(t.instructions)', ':filter' ), $queryBuilder->expr()->like( 'LOWER(fm.lastname)', ':filter' ) ), $queryBuilder->expr()->like( 'LOWER(to.lastname)', ':filter' )
                            )
                    );
                    break;
                case DStore::GT:
                    $queryBuilder->where(
                            $queryBuilder->expr()->gt( 'LOWER(t.instructions)', ':filter' )
                    );
            }
            $queryBuilder->setParameter( 'filter', strtolower( $dstore['filter'][DStore::VALUE] ) );
            if( !empty( $transferIds ) )
            {
                $queryBuilder->orWhere( 't.id IN (:transfer_ids)' );
                $queryBuilder->setParameter( 'transfer_ids', $transferIds );
            }
        }

        $data = $queryBuilder->getQuery()->getResult();
        $count = $em->getRepository( 'App\Entity\Asset\Transfer' )->count([]);
        $view = FOSRestView::create();
        $view->setData( $data );
        $view->setHeader( 'Content-Range', 'items ' . $offset . '-' . ($offset + $limit) . '/' . $count );
        $handler = $this->get( 'fos_rest.view_handler' );
        return $handler->handle( $view );
    }

    /**
     * @View()
     */
    public function getTransferAction( $id )
    {
        $this->denyAccessUnlessGranted( 'ROLE_ADMIN', null, 'Unable to access this page!' );
        $em = $this->getDoctrine()->getManager();
        if( $this->isGranted( 'ROLE_SUPER_ADMIN' ) )
        {
            $em->getFilters()->disable( 'softdeleteable' );
        }
        $transfer = $this->getDoctrine()
                        ->getRepository( 'App\Entity\Asset\Transfer' )->find( $id );
        if( $transfer !== null )
        {
            $logUtil = $this->log;
            $logUtil->getLog( 'App\Entity\Asset\TransferLog', $id );
            $history = $logUtil->translateIdsToText();
            $formUtil = $this->formUtil;
            $formUtil->saveDataTimestamp( 'transfer' . $transfer->getId(), $transfer->getUpdatedAt() );

            $form = $this->createForm( TransferType::class, $transfer, ['allow_extra_fields' => true] );
            $transfer->setHistory( $history );
            $form->add( 'history', TextareaType::class, ['data' => $history] );
            return $form->getViewData();
        }
        else
        {
            throw $this->createNotFoundException( 'Not found!' );
        }
    }

    /**
     * @View()
     */
    public function postTransferAction( $id, Request $request )
    {
        return $this->putTransferAction( $id, $request );
    }

    /**
     * @View()
     */
    public function putTransferAction( $id, Request $request )
    {
        $this->denyAccessUnlessGranted( 'ROLE_ADMIN', null, 'Unable to access this page!' );
        $em = $this->getDoctrine()->getManager();
        $response = new Response();
        $data = $request->request->all();
        if( $id === "null" )
        {
            $transfer = new Transfer();
        }
        else
        {
            $transfer = $em->getRepository( 'App\Entity\Asset\Transfer' )->find( $id );
            $formUtil = $this->formUtil;
            if( $formUtil->checkDataTimestamp( 'transfer' . $transfer->getId(), $transfer->getUpdatedAt() ) === false )
            {
                throw new Exception( "data.outdated", 400 );
            }
        }
        $form = $this->createForm( TransferType::class, $transfer, ['allow_extra_fields' => true] );
        try
        {
            $form->submit( $data );
            if( $form->isValid() )
            {
                $transfer = $form->getData();
                $em->persist( $transfer );
                $transferItems = $transfer->getItems();
                $transferStatus = $transfer->getStatus();

                if( $transferStatus->isLocationDestination() )
                {
                    foreach( $transferItems as $t )
                    {
                        $location = $transfer->getDestinationLocation();
                        $locationEntity = $location->getEntity();
                        $locationText = $locationEntity !== null ? $locationEntity->getName() : $location->getType()->getName();
                        $t->getAsset()->setLocation( $location )->setLocationText( $locationText );
                    }
                }
                else
                {
                    if( $transferStatus->isInTransit() )
                    {
                        $inTransit = $this->get( 'translator' )->trans( 'asset.in_transit' );
                        $queryBuilder = $em->createQueryBuilder()->select( ['l'] )
                                ->from( 'App\Entity\Asset\Location', 'l' )
                                ->join( 'l.type', 't' )
                                ->where( 't.name = :type' )
                                ->setParameter( 'type', $inTransit );
                        $data = $queryBuilder->getQuery()->getResult();
                        if( !empty( $data ) )
                        {
                            foreach( $transferItems as $t )
                            {
                                $t->getAsset()->setLocation( $data[0] )->setLocationText( $inTransit );
                            }
                        }
                    }
                    else
                    {
                        if( $transferStatus->isLocationUnknown() )
                        {
                            $unknown = $this->get( 'translator' )->trans( 'common.unknown' );
                            $queryBuilder = $em->createQueryBuilder()->select( ['l'] )
                                    ->from( 'App\Entity\Asset\Location', 'l' )
                                    ->join( 'l.type', 't' )
                                    ->where( 't.name = :type' )
                                    ->setParameter( 'type', $unknown );
                            $data = $queryBuilder->getQuery()->getResult();
                            if( !empty( $data ) )
                            {
                                foreach( $transferItems as $t )
                                {
                                    $t->getAsset()->setLocation( $data[0] )->setLocationText( $unknown );
                                }
                            }
                        }
                    }
                }
                $em->flush();
                $response->setStatusCode( $request->getMethod() === 'POST' ? 201 : 204  );
                $response->headers->set( 'Location', $this->generateUrl(
                                'app_admin_api_transfers_get_transfer', array('id' => $transfer->getId()), true // absolute
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
    public function patchTransferAction( $id, Request $request )
    {
        $formProcessor = $this->formUtil;
        $data = $formProcessor->getJsonData( $request );
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository( 'App\Entity\Asset\Transfer' );
        $transfer = $repository->find( $id );
        if( $transfer !== null )
        {
            if( isset( $data['field'] ) && is_bool( $formProcessor->strToBool( $data['value'] ) ) )
            {
                $value = $formProcessor->strToBool( $data['value'] );
                switch( $data['field'] )
                {
                    case 'active':
                        $transfer->setActive( $value );
                        break;
                }

                $em->persist( $transfer );
                $em->flush();
            }
        }
    }

    /**
     * @View(statusCode=204)
     */
    public function deleteTransferAction( $id )
    {
        $this->denyAccessUnlessGranted( 'ROLE_ADMIN', null, 'Unable to access this page!' );
        $em = $this->getDoctrine()->getManager();
        if( $this->isGranted( 'ROLE_SUPER_ADMIN' ) )
        {
            $em->getFilters()->disable( 'softdeleteable' );
        }
        $transfer = $em->getRepository( 'App\Entity\Asset\Transfer' )->find( $id );
        if( $transfer !== null )
        {
            $em->getFilters()->enable( 'softdeleteable' );
            $em->remove( $transfer );
            $em->flush();
        }
        else
        {
            throw $this->createNotFoundException( 'Not found!' );
        }
    }

}
