<?php

Namespace App\Controller\Api\Admin\Asset;

use App\Util\DStore;
use App\Util\Log;
use App\Util\Form as FormUtil;
use App\Entity\Asset\Issue;
use App\Entity\Asset\Trailer;
use App\Entity\Common\Person;
use App\Form\Admin\Asset\IssueType;
use Doctrine\Common\Collections\ArrayCollection;
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

class IssuesController extends FOSRestController
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
    public function getIssuesAction( Request $request )
    {
        $this->denyAccessUnlessGranted( 'ROLE_ADMIN', null, 'Unable to access this page!' );
        $dstore = $this->dstore->gridParams( $request, 'id' );
        switch( $dstore['sort-field'] )
        {
            case 'barcode':
                $sortField = 'bc.barcode';
                break;
            case 'status_text':
                $sortField = 's.status';
                break;
            case 'type_text':
                $sortField = 't.type';
                break;
            case 'trailer_text':
                $sortField = 'tr.name';
                break;
            case 'assigned_to_text':
                $sortField = 'assigned_to_text';
                break;
            default:
                $sortField = 'i.' . $dstore['sort-field'];
        }
        $em = $this->getDoctrine()->getManager();
        if( $this->isGranted( 'ROLE_SUPER_ADMIN' ) )
        {
            $em->getFilters()->disable( 'softdeleteable' );
        }
        $columns = ['i'];
        if( $this->isGranted( 'ROLE_SUPER_ADMIN' ) )
        {
            $columns[] = 'i.deletedAt AS deleted_at';
        }
        $issueIds = [];
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
                $queryBuilder = $em->createQueryBuilder()->select( 'i.id' )
                        ->from( 'App\Entity\Asset\Issue', 'i' )
                        ->join( 'i.items', 'ii' )
                        ->join( 'ii.asset', 'a' );
                $queryBuilder->where( 'a.id IN (:asset_ids)' );
                $queryBuilder->setParameter( 'asset_ids', $assetIds );
                $issueData = $queryBuilder->getQuery()->getResult();
                $issueIds = [];
                foreach( $issueData as $i )
                {
                    $issueIds[] = $i['id'];
                }
            }
        }

        $columns = ['i.id', 'i.priority', 'tr.name AS trailer_text', 'i.summary', 's.status AS status_text', 't.type AS type_text',
            "CONCAT(CONCAT(p.firstname,' '),p.lastname) AS assigned_to_text", 'i.billable'
        ];
        $queryBuilder = $em->createQueryBuilder()->select( $columns )
                ->from( 'App\Entity\Asset\Issue', 'i' )
                ->innerJoin( 'i.status', 's' )
                ->innerJoin( 'i.type', 't' )
                ->leftJoin( 'i.trailer', 'tr' )
                ->leftJoin( 'i.assignedTo', 'p' )
                ->leftJoin( 'i.items', 'ii' )
                ->leftJoin( 'ii.asset', 'a' )
                ->leftJoin( 'a.barcodes', 'b' )
                ->orderBy( $sortField, $dstore['sort-direction'] )
                ->distinct();

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
                                            $queryBuilder->expr()->like( 'LOWER(i.summary)', ':filter' ), $queryBuilder->expr()->like( 'LOWER(i.details)', ':filter' ) ), $queryBuilder->expr()->like( 'LOWER(p.lastname)', ':filter' )
                            )
                    );
                    break;
                case DStore::GT:
                    $queryBuilder->where(
                            $queryBuilder->expr()->gt( 'LOWER(i.summary)', ':filter' )
                    );
            }
            $queryBuilder->setParameter( 'filter', strtolower( $dstore['filter'][DStore::VALUE] ) );
            if( !empty( $issueIds ) )
            {
                $queryBuilder->orWhere( 'i.id IN (:issue_ids)' );
                $queryBuilder->setParameter( 'issue_ids', $issueIds );
            }
        }
        $data = $queryBuilder->getQuery()->getResult();
        $count = $em->getRepository( 'App\Entity\Asset\Issue' )->count([]);
        $view = FOSRestView::create();
        $view->setData( $data );
        $view->setHeader( 'Content-Range', 'items ' . $offset . '-' . ($offset + $limit) . '/' . $count );
        $handler = $this->get( 'fos_rest.view_handler' );
        return $handler->handle( $view );
    }

    /**
     * @View()
     */
    public function getIssueAction( $id )
    {

        $this->denyAccessUnlessGranted( 'ROLE_ADMIN', null, 'Unable to access this page!' );
        $em = $this->getDoctrine()->getManager();
        if( $this->isGranted( 'ROLE_SUPER_ADMIN' ) )
        {
            $em->getFilters()->disable( 'softdeleteable' );
        }
        $issue = $this->getDoctrine()
                        ->getRepository( 'App\Entity\Asset\Issue' )->find( $id );

        if( $issue !== null )
        {
            $logUtil = $this->log;
            $logUtil->getLog( 'App\Entity\Asset\IssueLog', $id );
            $history = $logUtil->translateIdsToText();
            $formUtil = $this->formUtil;
            $formUtil->saveDataTimestamp( 'issue' . $issue->getId(), $issue->getUpdatedAt() );

            $form = $this->createForm( IssueType::class, $issue, ['allow_extra_fields' => true] );
            $issue->setHistory( $history );
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
    public function postIssueAction( $id, Request $request )
    {
        return $this->putIssueAction( $id, $request );
    }

    /**
     * @View()
     */
    public function putIssueAction( $id, Request $request )
    {
        $this->denyAccessUnlessGranted( 'ROLE_ADMIN', null, 'Unable to access this page!' );
        $em = $this->getDoctrine()->getManager();
        $response = new Response();
        $data = $request->request->all();
        $originalItems = null;
        if( $id === "null" )
        {
            $issue = new Issue();
        }
        else
        {
            $issue = $em->getRepository( 'App\Entity\Asset\Issue' )->find( $id );
            $originalItems = new ArrayCollection();
            foreach( $issue->getItems() as $item )
            {
                $originalItems->add( $item );
            }
            $formUtil = $this->formUtil;
            if( $formUtil->checkDataTimestamp( 'issue' . $issue->getId(), $issue->getUpdatedAt() ) === false )
            {
                throw new Exception( "data.outdated", 400 );
            }
        }
        $form = $this->createForm( IssueType::class, $issue, ['allow_extra_fields' => true] );
        try
        {
            $form->submit( $data, true );
            if( $form->isValid() )
            {
                $issue = $form->getData();
                $issueItems = $issue->getItems();

                foreach( $issueItems as $i => $item )
                {
                    $item->getAsset()->setStatus( $form['items'][$i]['status']->getData() );
                }
                if( !empty( $originalItems ) )
                {
                    foreach( $originalItems as $item )
                    {
                        if( false === $issueItems->contains( $item ) )
                        {
                            $issue->removeItem( $item );
                        }
                    }
                }

                $em->persist( $issue );
                $em->flush();
                $response->setStatusCode( $request->getMethod() === 'POST' ? 201 : 204  );
                $response->headers->set( 'Location', $this->generateUrl(
                                'get_issue', array('id' => $issue->getId()), true // absolute
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
    public function patchIssueAction( $id, Request $request )
    {
        $formProcessor = $this->formUtil;
        $data = $formProcessor->getJsonData( $request );
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository( 'App\Entity\Asset\Issue' );
        $issue = $repository->find( $id );
        if( $issue !== null )
        {
            if( isset( $data['field'] ) && is_bool( $formProcessor->strToBool( $data['value'] ) ) )
            {
                $value = $formProcessor->strToBool( $data['value'] );
                switch( $data['field'] )
                {
                    case 'active':
                        $issue->setActive( $value );
                        break;
                }

                $em->persist( $issue );
                $em->flush();
            }
        }
    }

    /**
     * @View(statusCode=204)
     */
    public function deleteIssueAction( $id )
    {
        $this->denyAccessUnlessGranted( 'ROLE_ADMIN', null, 'Unable to access this page!' );
        $em = $this->getDoctrine()->getManager();
        if( $this->isGranted( 'ROLE_SUPER_ADMIN' ) )
        {
            $em->getFilters()->disable( 'softdeleteable' );
        }
        $issue = $em->getRepository( 'App\Entity\Asset\Issue' )->find( $id );
        if( $issue !== null )
        {
            $em->getFilters()->enable( 'softdeleteable' );
            $em->remove( $issue );
            $em->flush();
        }
        else
        {
            throw $this->createNotFoundException( 'Not found!' );
        }
    }

}
