<?php

Namespace App\Form;

use App\Form\Common\PersonType;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TestType extends AbstractType
{

    private $authorizationChecker;
    private $roles = null;

    public function __construct( AuthorizationCheckerInterface $authorizationChecker, $roles = [] )
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->roles = [];
        $role = null;
        foreach( $roles as $n => $r )
        {
            if ($n === 'ROLE_API') {
                continue;
            }
            $role = new \stdClass();
            $role->name = $n;
            $role->value = $n;
            $this->roles[] = $role;
        }
    }

    public function buildForm( FormBuilderInterface $builder, array $options )
    {
        if( $this->authorizationChecker->isGranted( 'ROLE_ADMIN_USER_ADMIN' ) )
        {
            $builder
                    ->add( 'roles', ChoiceType::class, ['choices' => $this->roles,
                        'multiple' => true,
                        'expanded' => true,
                        'label' => 'common.roles',
                        'choice_label' => 'name',
                        'choice_translation_domain' => false,
                        //'translation_domain' => false,
                        'attr' => [ 'data-type' => 'user-role-cb']] );
        }
    }

    public function configureOptions( OptionsResolver $resolver )
    {
        $resolver->setDefaults( array(
        ) );
    }

    public function getName()
    {
        return 'test';
    }

}
