<?php

namespace AppBundle\Controller\Admin\Asset;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Form\Admin\Asset\AssetType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Description of AssetController
 *
 * @author bgamrat
 */
class AssetController extends Controller
{

    /**
     * @Route("/admin/asset/asset")
     * @Method("GET")
     */
    public function indexAction( Request $request )
    {
        $this->denyAccessUnlessGranted( 'ROLE_ADMIN', null, 'Unable to access this page!' );

        $assetForm = $this->createForm( AssetType::class, null, [] );

        return $this->render( 'admin/asset/assets.html.twig', array(
                    'asset_form' => $assetForm->createView(),
                    'base_dir' => realpath( $this->container->getParameter( 'kernel.root_dir' ) . '/..' ),
                ) );
    }

}
