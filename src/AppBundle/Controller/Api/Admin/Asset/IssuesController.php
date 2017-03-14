<?php

namespace AppBundle\Controller\Api\Admin\Asset;

use AppBundle\Util\DStore;
use AppBundle\Entity\Asset\Issue;
use AppBundle\Entity\Asset\Trailer;
use AppBundle\Entity\Common\Person;
use AppBundle\Form\Admin\Asset\IssueType;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations\View;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class IssuesController extends FOSRestController
{

    /**
     * @View()
     */
    public function getIssuesAction( Request $request )
    {
        $this->denyAccessUnlessGranted( 'ROLE_ADMIN', null, 'Unable to access this page!' );
        $dstore = $this->get( 'app.util.dstore' )->gridParams( $request, 'id' );
        switch( $dstore['sort-field'] )
        {
            case 'barcode':
                $sortField = 'bc.barcode';
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
            $assetData = $em->getRepository( 'AppBundle\Entity\Asset\Asset' )->findByBarcode( $dstore['filter'][DStore::VALUE] );
            if( !empty( $assetData ) )
            {
                $assetIds = [];
                foreach( $assetData as $a )
                {
                    $assetIds[] = $a->getId();
                }
                $queryBuilder = $em->createQueryBuilder()->select( 'i.id' )
                        ->from( 'AppBundle\Entity\Asset\Issue', 'i' )
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

        $columns = ['i.id', 'i.priority', 'i.summary', 's.status', 't.type',
            "CONCAT(CONCAT(p.firstname,' '),p.lastname) AS assigned_to",
            'b.barcode'];
        $queryBuilder = $em->createQueryBuilder()->select( $columns )
                ->from( 'AppBundle\Entity\Asset\Issue', 'i' )
                ->join( 'i.status', 's' )
                ->join( 'i.type', 't' )
                ->leftJoin( 'i.assignedTo', 'p' )
                ->leftJoin( 'i.items', 'ii' )
                ->join( 'ii.asset', 'a' )
                ->join( 'a.barcodes', 'b' )
                ->orderBy( $sortField, $dstore['sort-direction'] );

        if( $dstore['limit'] !== null )
        {
            $queryBuilder->setMaxResults( $dstore['limit'] );
        }
        if( $dstore['offset'] !== null )
        {
            $queryBuilder->setFirstResult( $dstore['offset'] );
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

        return array_values( $data );
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
                        ->getRepository( 'AppBundle\Entity\Asset\Issue' )->find( $id );
        if( $issue !== null )
        {
            $data = [
                'id' => $id,
                'priority' => $issue->getPriority(),
                'type' => $issue->getType(),
                'status' => $issue->getStatus(),
                'assigned_to' => $issue->getAssignedTo(),
                'summary' => $issue->getSummary(),
                'details' => $issue->getDetails(),
                'items' => $issue->getItems(),
                'client_billable' => $issue->isClientBillable(),
                'cost' => $issue->getCost(),
                'trailer' => $issue->getTrailer(),
                'replaced' => $issue->isReplaced(),
                'created' => $issue->getCreated()->format( 'Y-m-d' ),
                'updated' => $issue->getUpdated()->format( 'Y-m-d' )
            ];

            $logUtil = $this->get( 'app.util.log' );
            $logUtil->getLog( 'AppBundle\Entity\Asset\IssueLog', $id );
            $data['history'] = $logUtil->translateIdsToText();
            $formUtil = $this->get( 'app.util.form' );
            $formUtil->saveDataTimestamp( 'issue' . $issue->getId(), $issue->getUpdated() );
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
        if( $id === "null" )
        {
            $issue = new Issue();
        }
        else
        {
            $issue = $em->getRepository( 'AppBundle\Entity\Asset\Issue' )->find( $id );
            $formUtil = $this->get( 'app.util.form' );
            if( $formUtil->checkDataTimestamp( 'issue' . $issue->getId(), $issue->getUpdated() ) === false )
            {
                throw new Exception( "data.outdated", 400 );
            }
        }
        $form = $this->createForm( IssueType::class, $issue, ['allow_extra_fields' => true] );
        try
        {
            $form->submit( $data );
            if( $form->isValid() )
            {
                $issue = $form->getData();
                $em->persist( $issue );
                $em->flush();
                $response->setStatusCode( $request->getMethod() === 'POST' ? 201 : 204  );
                $response->headers->set( 'Location', $this->generateUrl(
                                'app_admin_api_issues_get_issue', array('id' => $issue->getId()), true // absolute
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
        $formProcessor = $this->get( 'app.util.form' );
        $data = $formProcessor->getJsonData( $request );
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository( 'AppBundle\Entity\Asset\Issue' );
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
        $issue = $em->getRepository( 'AppBundle\Entity\Asset\Issue' )->find( $id );
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