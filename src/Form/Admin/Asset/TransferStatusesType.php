<?php

Namespace App\Form\Admin\Asset;

use App\Form\Admin\Asset\TransferStatusType;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TransferStatusesType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm( FormBuilderInterface $builder, array $options )
    {
        $builder
                ->add( 'statuses', CollectionType::class, [
                    'entry_type' => TransferStatusType::class,
                    'by_reference' => false,
                    'required' => false,
                    'label' => false,
                    'empty_data' => null,
                    'allow_add' => true,
                    'allow_delete' => false,
                    'delete_empty' => true,
                    'prototype_name' => '__status__'
                ] )
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions( OptionsResolver $resolver )
    {
        $resolver->setDefaults( array(
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'statuses',
        ) );
    }

    public function getName()
    {
        return 'transfer_statuses';
    }

}
