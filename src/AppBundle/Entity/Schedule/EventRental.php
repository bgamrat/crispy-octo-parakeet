<?php

Namespace AppBundle\Entity\Schedule;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use AppBundle\Entity\Traits\Versioned\Comment;
use AppBundle\Entity\Traits\Id;
use AppBundle\Entity\Traits\Versioned\Cost;

/**
 * EventRental
 *
 * @ORM\Entity()
 * @ORM\Table(name="event_rental")
 * @Gedmo\Loggable(logEntryClass="AppBundle\Entity\Schedule\EventLog")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 */
class EventRental
{

    use Id,
        Comment,
        Cost,
        SoftDeleteableEntity,
        TimestampableEntity;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\ManyToOne(targetEntity="Event", inversedBy="rentals")
     * @ORM\JoinColumn(name="rental_id", referencedColumnName="id")
     */
    private $id;
    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Asset\Category")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id")
     * @Gedmo\Versioned
     */
    private $category;
        /**
     * @var int
     * @Gedmo\Versioned
     * @ORM\OrderBy({"name" = "ASC"})
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Asset\Vendor")
     * @ORM\JoinColumn(name="vendor_id", referencedColumnName="id")
     */
    protected $vendor = null;

        /**
     * Set category
     *
     * @param string $category
     *
     * @return EventRental
     */
    public function setCategory( $category )
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set vendor
     *
     * @param int $vendor
     *
     * @return EventRental
     */
    public function setVendor( $vendor )
    {
        $this->vendor = $vendor;

        return $this;
    }

    /**
     * Get vendor
     *
     * @return int
     */
    public function getVendor()
    {
        return $this->vendor;
    }
}
