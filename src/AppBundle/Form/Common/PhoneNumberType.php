<?php

namespace AppBundle\Form\Common;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Doctrine\ORM\EntityRepository;

class PhoneNumberType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm( FormBuilderInterface $builder, array $options )
    {

        $builder
                ->add( 'id', HiddenType::class, ['label' => false] )
                ->add( 'type', EntityType::class, [
                    'class' => 'AppBundle\Entity\Common\PhoneNumberType',
                    'choice_label' => 'type',
                    'multiple' => false,
                    'expanded' => false,
                    'required' => true,
                    'choice_translation_domain' => false
                ] )
                ->add( 'phone_number', TextType::class, [
                ] )
                ->add( 'comment', TextType::class, [
                    'label' => false
                ] )
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions( OptionsResolver $resolver )
    {
        $resolver->setDefaults( array(
            'data_class' => 'AppBundle\Entity\Common\PhoneNumber'
        ) );
    }

    public function getName()
    {
        return 'phone_number';
    }

}
