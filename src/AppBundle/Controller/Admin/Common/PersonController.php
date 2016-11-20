<?php

namespace AppBundle\Controller\Admin\Common;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Form\Common\PersonType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Description of PersonController
 *
 * @author bgamrat
 */
class PersonController extends Controller
{

    /**
     * @Route("/admin/common/person")
     * @Method("GET")
     */
    public function indexAction( Request $request )
    {
        $this->denyAccessUnlessGranted( 'ROLE_ADMIN', null, 'Unable to access this page!' );

        $personForm = $this->createForm( PersonType::class, null, [] );

        return $this->render( 'admin/common/people.html.twig', array(
                    'person_form' => $personForm->createView(),
                    'base_dir' => realpath( $this->container->getParameter( 'kernel.root_dir' ) . '/..' ),
                ) );
    }

}