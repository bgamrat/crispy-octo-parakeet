<?php

namespace AppBundle\Controller\Admin;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\UserBundle\Model\UserManager as BaseUserManager;
use AppBundle\Entity\User;
use AppBundle\Form\Admin\User\UserType;

class UserController extends Controller
{

    /**
     * @Route("/admin/user", name="adminuser")
     */
    public function indexAction( Request $request )
    {
        $this->denyAccessUnlessGranted( 'ROLE_ADMIN', null, 'Unable to access this page!' );

        $user = new User();
        $user_form = $this->createForm(new UserType(), $user);

        return $this->render( 'admin/user/index.html.twig', array(
                    'user_form' => $user_form->createView(),
                    'base_dir' => realpath( $this->container->getParameter( 'kernel.root_dir' ) . '/..' ),
                ) );
    }

    /**
     * @Route("/api/admin/user/list")
     */
    public function apiUserListAction()
    {
        $this->denyAccessUnlessGranted( 'ROLE_ADMIN', null, 'Unable to access this page!' );
        $users = $this->get( 'fos_user.user_manager' )->findUsers();
        
        $data = [];
        foreach ($users as $u) {
            $item = ['username' => $u->getUsername(),
                'email' => $u->getEmail(),
                'enabled' => $u->isEnabled()];
            $data[] = $item;
        }

        // calls json_encode and sets the Content-Type header
        return new JsonResponse( $data );
    }

}
