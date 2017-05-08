<?php

namespace AppBundle\Entity\Asset;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Entity\Common\Person;

/**
 * Vendor
 *
 * @ORM\Table(name="vendor")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\VendorRepository")
 * @Gedmo\Loggable
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 */
class Vendor
{

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=64, nullable=true, unique=true)
     * @Gedmo\Versioned
     */
    private $name;
    /**
     * @var boolean
     * @Gedmo\Versioned
     * @ORM\Column(name="active", type="boolean") 
     */
    private $active = true;
    /**
     * @var string
     * @Gedmo\Versioned
     * @ORM\Column(type="string", length=256, nullable=true)
     */
    private $comment;
    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Common\Person", cascade={"persist"})
     * @ORM\JoinTable(name="vendor_contacts",
     *      joinColumns={@ORM\JoinColumn(name="person_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="vendor_id", referencedColumnName="id", unique=true)}
     *      )
     */
    private $contacts = null;
    /**
     * @var ArrayCollection $brands
     * @ORM\ManyToMany(targetEntity="Brand")
     * @ORM\JoinTable(name="vendor_brand",
     *      joinColumns={@ORM\JoinColumn(name="brand_id", referencedColumnName="id", nullable=false)},
     *      inverseJoinColumns={@ORM\JoinColumn(name="vendor_id", referencedColumnName="id", nullable=false)}
     *      )
     */
    protected $brands = null;
    /**
     * @var boolean
     * @Gedmo\Versioned
     * @ORM\Column(name="rma_required", type="boolean") 
     */
    private $rmaRequired = false;
    /**
     * @var string
     * @Gedmo\Versioned
     * @ORM\Column(type="string", length=256, nullable=true)
     */
    private $serviceInstructions = null;
    /**
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    private $created;
    /**
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    private $updated;
    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Gedmo\Versioned
     */
    private $deletedAt;

    public function __construct()
    {
        $this->contacts = new ArrayCollection();
        $this->brands = new ArrayCollection();
    }

    /**
     * Set id
     *
     * @return integer
     */
    public function setId( $id )
    {
        $this->id = $id;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Vendor
     */
    public function setName( $name )
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function setActive( $active )
    {
        $this->active = $active;
    }

    public function isActive()
    {
        return $this->active;
    }

    /**
     * Set comment
     *
     * @param string $comment
     *
     * @return Vendor
     */
    public function setComment( $comment )
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    public function getBrandData()
    {
        $data = [];
        foreach( $this->brands as $b )
        {
            $data[] = ['id' => $b->getId(), 'name' => $b->getName()];
        }
        return $data;
    }

    public function getBrands()
    {
        return $this->brands->toArray();
    }

    public function addBrand( Brand $brand )
    {
        if( !$this->brands->contains( $brand ) )
        {
            $this->brands->add( $brand );
        }
    }

    public function removeBrand( Brand $brand )
    {
        $this->brands->removeElement( $brand );
    }

    public function getContacts()
    {
        return $this->contacts->toArray();
    }

    public function addContact( Person $contact )
    {
        if( !$this->contacts->contains( $contact ) )
        {
            $this->contacts->add( $contact );
        }
    }

    public function removeContact( Person $contact )
    {
        $this->contacts->removeElement( $contact );
    }

    public function setRmaRequired( $rmaRequired )
    {
        $this->rmaRequired = $rmaRequired;
    }

    public function isRmaRequired()
    {
        return $this->rmaRequired;
    }

    /**
     * Set ServiceInstructions
     *
     * @param string $serviceInstructions
     *
     * @return Vendor
     */
    public function setServiceInstructions( $sServiceInstructions )
    {
        $this->serviceInstructions = $serviceInstructions;

        return $this;
    }

    /**
     * Get ServiceInstructions
     *
     * @return string
     */
    public function getServiceInstructions()
    {
        return $this->serviceInstructions;
    }

    public function getUpdated()
    {
        return $this->updated;
    }

    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    public function setDeletedAt( $deletedAt )
    {
        $this->deletedAt = $deletedAt;
        $this->setActive( false );
    }

}
