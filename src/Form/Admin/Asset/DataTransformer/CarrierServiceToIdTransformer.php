<?php

Namespace App\Form\Admin\Asset\DataTransformer;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class CarrierServiceToIdTransformer implements DataTransformerInterface
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Transforms an object (carrierService) to a string (id).
     *
     * @param  Issue|null $carrierService
     * @return string
     */
    public function transform($carrierService)
    {
        if (null === $carrierService) {
            return '';
        }

        if (is_string($carrierService)) {
            return $carrierService;
        }
        
        return $carrierService->getId();
    }

    /**
     * Transforms a string (id) to an object (carrierService).
     *
     * @param  string $carrierServiceId
     * @return Issue|null
     * @throws TransformationFailedException if object (carrierService) is not found.
     */
    public function reverseTransform($carrierServiceId = null)
    {
        // no carrierService id? It's optional, so that's ok
        if (!$carrierServiceId) {
            return;
        }

        $carrierService = $this->em
            ->getRepository('App\Entity\Asset\carrierService')
            ->find($carrierServiceId)
        ;

        if (null === $carrierService) {
            throw new TransformationFailedException(sprintf(
                'An carrierService with id "%s" does not exist!',
                $carrierServiceId
            ));
        }
        return $carrierService;
    }
}