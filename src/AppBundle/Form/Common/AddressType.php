<?php

namespace AppBundle\Form\Common;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class AddressType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm( FormBuilderInterface $builder, array $options )
    {
        $builder
                ->add( 'type', EntityType::class, [
                    'class' => 'AppBundle:AddressType',
                    'choice_label' => 'type',
                    'multiple' => false,
                    'expanded' => false,
                    'required' => true,
                    'label' => 'common.type',
                    'choice_translation_domain' => false
                ] )
                ->add( 'street1', TextType::class, ['label' => 'common.street'] )
                ->add( 'street2', TextType::class, ['label' => 'common.street2'] )
                ->add( 'city', TextType::class, ['label' => 'common.city'] )
                ->add( 'state_province', TextType::class, ['label' => 'common.state_province'] )
                ->add( 'postal_code', TextType::class, ['label' => 'common.postal_code'] )
                ->add( 'country', CountryType::class, ['label' => 'common.country',
                    'choices_as_values' => true,
                    'preferred_choices' => ['US' => 'US', 'CA' => 'CA', 'MX' => 'MX']] )
                ->add( 'comment', TextareaType::class , [
                    'required' => false
                ] )
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions( OptionsResolver $resolver )
    {
        $resolver->setDefaults( array(
            'data_class' => 'AppBundle\Entity\Address'
        ) );
    }

    public function getName()
    {
        return 'address';
    }

}