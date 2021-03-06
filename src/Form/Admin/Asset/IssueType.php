<?php

Namespace App\Form\Admin\Asset;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Doctrine\ORM\EntityManager;
use App\Form\Common\DataTransformer\PersonToIdTransformer;
use App\Form\Admin\Common\BillToType;

class IssueType extends AbstractType
{

    private $em;

    public function __construct( EntityManager $em )
    {
        $this->em = $em;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm( FormBuilderInterface $builder, array $options )
    {
        $defaultStatus = $this->em->getRepository( 'App\Entity\Asset\IssueStatus' )->findOneBy( ['default' => true] );
        $defaultType = $this->em->getRepository( 'App\Entity\Asset\IssueType' )->findOneBy( ['default' => true] );
        $builder
                ->add( 'id', HiddenType::class, ['label' => false] )
                ->add( 'created_at', DateTimeType::class, [
                    'label' => 'common.created',
                    'widget' => 'single_text',
                    'format' => 'yyyy-MM-dd HH:mm:ss',
                    'required' => false,
                    'disabled' => true
                ] )
                ->add( 'updated_at', DateTimeType::class, [
                    'label' => 'common.updated',
                    'widget' => 'single_text',
                    'format' => 'yyyy-MM-dd HH:mm:ss',
                    'required' => false,
                    'disabled' => true
                ] )
                ->add( 'trailer', EntityType::class, [
                    'class' => 'App\Entity\Asset\Trailer',
                    'choice_label' => 'name',
                    'choice_value' => 'name',
                    'multiple' => false,
                    'expanded' => false,
                    'required' => false,
                    'label' => false,
                    'choice_translation_domain' => false
                ] )
                ->add( 'priority', IntegerType::class, ['label' => 'issue.priority'] )
                ->add( 'type', EntityType::class, [
                    'class' => 'App\Entity\Asset\IssueType',
                    'choice_label' => 'type',
                    'multiple' => false,
                    'expanded' => false,
                    'required' => true,
                    'label' => 'issue.type',
                    'preferred_choices' => function($type, $key, $index)
                    {
                        return $type->isInUse();
                    },
                    'data' => $this->em->getReference( 'App\Entity\Asset\IssueType', $defaultType->getId() ),
                    'choice_translation_domain' => false
                ] )
                ->add( 'status', EntityType::class, [
                    'class' => 'App\Entity\Asset\IssueStatus',
                    'choice_label' => 'status',
                    'multiple' => false,
                    'expanded' => false,
                    'required' => true,
                    'label' => 'issue.status',
                    'preferred_choices' => function($status, $key, $index)
                    {
                        return $status->isInUse();
                    },
                    'data' => $this->em->getReference( 'App\Entity\Asset\IssueStatus', $defaultStatus->getId() ),
                    'choice_translation_domain' => false
                ] )
                ->add( 'assigned_to', TextType::class, ['label' => 'issue.assigned_to'] )
                ->add( 'replaced', CheckboxType::class, ['label' => 'issue.replaced'] )
                ->add( 'billable', CheckboxType::class, ['label' => 'common.billable'] )
                ->add( 'summary', TextType::class, ['label' => false] )
                ->add( 'details', TextareaType::class, [
                    'label' => false
                ] )
                ->add( 'notes', CollectionType::class, [
                    'entry_type' => IssueNoteType::class,
                    'by_reference' => false,
                    'required' => false,
                    'label' => false,
                    'empty_data' => null,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'delete_empty' => true,
                    'prototype_name' => '__note__'
                ] )
                ->add( 'items', CollectionType::class, [
                    'entry_type' => IssueItemType::class,
                    'by_reference' => false,
                    'required' => false,
                    'label' => false,
                    'empty_data' => null,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'delete_empty' => true,
                    'prototype_name' => '__item__'
                ] )
                ->add( 'cost', MoneyType::class, ['label' => 'common.cost', 'currency' => 'USD'] )
                ->add( 'bill_tos', CollectionType::class, [
                    'entry_type' => BillToType::class,
                    'by_reference' => false,
                    'required' => false,
                    'label' => false,
                    'empty_data' => null,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'delete_empty' => true,
                    'prototype_name' => '__bill_to__'
                ] )
                
        ;
        $builder->get( 'assigned_to' )
                ->addModelTransformer( new PersonToIdTransformer( $this->em ) );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions( OptionsResolver $resolver )
    {
        $resolver->setDefaults( array(
            'data_class' => 'App\Entity\Asset\Issue'
        ) );
    }

    public function getName()
    {
        return 'issue';
    }

}
