<?php

Namespace App\Entity\Schedule;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Common\Person;
use App\Entity\Client\Contract;
use App\Entity\Common\CategoryQuantity;
use App\Entity\Schedule\EventRental;
use App\Entity\Schedule\ClientEquipment;
use App\Entity\Asset\Trailer;
use App\Entity\Schedule\TimeSpan;
use App\Entity\Schedule\EventRole;
use App\Entity\Venue\Venue;
use App\Entity\Traits\Versioned\Comment;
use App\Entity\Traits\Versioned\Cost;
use App\Entity\Traits\Id;
use App\Entity\Traits\Versioned\Name;
use App\Entity\Traits\Versioned\Value;
use App\Entity\Traits\History;

/**
 * Event
 *
 * @ORM\Table(name="event")
 * @ORM\Entity()
 * @Gedmo\Loggable(logEntryClass="App\Entity\Schedule\EventLog")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 */
class Event
{

    use Comment,
        Cost,
        Id,
        Name,
        Value,
        TimestampableEntity,
        SoftDeleteableEntity,
        History;

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
     * @Gedmo\Versioned
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;
    /**
     * @Gedmo\Versioned
     * @ORM\Column(name="startdate", type="datetime", nullable=true, unique=false)
     */
    private $start = null;
    /**
     * @Gedmo\Versioned
     * @ORM\Column(name="enddate", type="datetime", nullable=true, unique=false)
     */
    private $end = null;
    /**
     * @var boolean
     * @Gedmo\Versioned
     * @ORM\Column(name="tentative", type="boolean")
     */
    private $tentative = false;
    /**
     * @var boolean
     * @Gedmo\Versioned
     * @ORM\Column(name="billable", type="boolean")
     */
    private $billable = true;
    /**
     * @var boolean
     * @Gedmo\Versioned
     * @ORM\Column(name="canceled", type="boolean")
     */
    private $canceled = false;
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Client\Client")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id", nullable=true)
     * @Gedmo\Versioned
     */
    private $client;
    /**
     * @Assert\NotBlank()
     * @ORM\ManyToOne(targetEntity="App\Entity\Venue\Venue")
     * @ORM\JoinColumn(name="venue_id", referencedColumnName="id", nullable=true)
     * @Gedmo\Versioned
     */
    private $venue;
    /**
     * @Assert\NotBlank()
     * @ORM\ManyToMany(targetEntity="App\Entity\Common\Person", cascade={"persist"})
     * @ORM\JoinTable(name="event_contact",
     *      joinColumns={@ORM\JoinColumn(name="person_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="event_id", referencedColumnName="id")}
     *      )
     */
    private $contacts = null;
    /**
     * @var ArrayCollection $contracts
     * @ORM\ManyToMany(targetEntity="App\Entity\Client\Contract", cascade={"persist"})
     * @ORM\JoinTable(name="event_contract",
     *      joinColumns={@ORM\JoinColumn(name="event_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="contract_id", referencedColumnName="id", onDelete="CASCADE", unique=true, nullable=false)}
     *      )
     */
    protected $contracts = null;
    /**
     * @var ArrayCollection $trailers
     * @ORM\ManyToMany(targetEntity="App\Entity\Asset\Trailer", cascade={"persist"})
     * @ORM\JoinTable(name="event_trailer",
     *      joinColumns={@ORM\JoinColumn(name="event_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="trailer_id", referencedColumnName="id", onDelete="CASCADE", unique=true, nullable=false)}
     *      )
     */
    protected $trailers = null;
    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Schedule\TimeSpan", cascade={"persist"})
     * @ORM\JoinTable(name="event_time_span",
     *      joinColumns={@ORM\JoinColumn(name="time_span_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="event_id", referencedColumnName="id", unique=true)}
     *      )
     */
    private $time_spans = null;
    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Schedule\EventRole", cascade={"persist"})
     */
    private $roles = null;
    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Common\CategoryQuantity", cascade={"persist"})
     * @ORM\JoinTable(name="event_category_quantity",
     *      joinColumns={@ORM\JoinColumn(name="event_id", referencedColumnName="id")}
     *      )
     */
    private $categoryQuantities;
    /**
     * @var ArrayCollection $rentals
     * @ORM\OrderBy({"id" = "ASC"})
     * @ORM\ManyToMany(targetEntity="App\Entity\Schedule\EventRental", cascade={"persist","remove"}, orphanRemoval=true)
     * @ORM\JoinTable(name="event_rentals",
     *      joinColumns={@ORM\JoinColumn(name="event_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="rental_id", referencedColumnName="id", unique=false, nullable=true)}
     *      )
     */
    protected $rentals;
    /**
     * @var ArrayCollection $clientEquipment
     * @ORM\OrderBy({"id" = "ASC"})
     * @ORM\ManyToMany(targetEntity="App\Entity\Schedule\ClientEquipment", cascade={"persist","remove"}, orphanRemoval=true)
     * @ORM\JoinTable(name="event_client_equipment",
     *      joinColumns={@ORM\JoinColumn(name="event_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="client_equipment_id", referencedColumnName="id", unique=false, nullable=true)}
     *      )
     */
    protected $client_equipment;

    public function __construct()
    {
        $this->contacts = new ArrayCollection();
        $this->contracts = new ArrayCollection();
        $this->trailers = new ArrayCollection();
        $this->time_spans = new ArrayCollection();
        $this->event_roles = new ArrayCollection();
        $this->rentals = new ArrayCollection();
        $this->client_equipment = new ArrayCollection();
    }

    /**
     * Set start
     *
     * @param float $start
     *
     * @return Event
     */
    public function setStart( $start )
    {
        $this->start = $start;

        return $this;
    }

    /**
     * Get start
     *
     * @return float
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Set end
     *
     * @param float $end
     *
     * @return Event
     */
    public function setEnd( $end )
    {
        $this->end = $end;

        return $this;
    }

    /**
     * Get end
     *
     * @return float
     */
    public function getEnd()
    {
        return $this->end;
    }

    public function setTentative( $tentative )
    {
        $this->tentative = $tentative;
    }

    public function isTentative()
    {
        return $this->tentative;
    }

    public function setCanceled( $canceled )
    {
        $this->canceled = $canceled;
    }

    public function isCanceled()
    {
        return $this->canceled;
    }

    public function setBillable( $billable )
    {
        $this->billable = $billable;
    }

    public function isBillable()
    {
        return $this->billable;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return Event
     */
    public function setDescription( $description )
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set client
     *
     * @param string $client
     *
     * @return Event
     */
    public function setClient( $client )
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Get client
     *
     * @return string
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Set venue
     *
     * @param string $venue
     *
     * @return Event
     */
    public function setVenue( Venue $venue )
    {
        $this->venue = $venue;

        return $this;
    }

    /**
     * Get venue
     *
     * @return string
     */
    public function getVenue()
    {
        return $this->venue;
    }

    public function getContacts()
    {
        return $this->contacts;
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

    public function getContracts()
    {
        return empty( $this->contracts ) ? [] : $this->contracts->toArray();
    }

    public function addContract( Contract $contract )
    {
        if( !$this->contracts->contains( $contract ) )
        {
            $this->contracts->add( $contract );
        }
        return $this;
    }

    public function removeContract( Contract $contract )
    {
        $this->contracts->removeElement( $contract );
    }

    public function getTrailers()
    {
        $return = [];

        foreach( $this->trailers as $t )
        {
            $et = [];
            $et['id'] = $t->getId();
            $et['name'] = $t->getName();
            $return[] = $et;
        }
        return $return;
    }

    public function setTrailers( $trailers )
    {
        $this->trailers->clear();
        foreach( $trailers as $t )
        {
            $this->trailers->add( $t );
        }
        return $this;
    }

    public function setCategoryQuantities( $categoryQuantities )
    {
        $this->categoryQuantities->clear();
        foreach( $categoryQuantities as $cq )
        {
            $this->addCategoryQuantities( $cq );
        }
        return $this;
    }

    public function getCategoryQuantities( $full = true )
    {
        $categoryQuantities = [];
        if( count( $this->categoryQuantities ) > 0 )
        {
            if( $full === false )
            {
                foreach( $this->categoryQuantities as $cq )
                {
                    $categoryQuantities[] = ['id' => $cq->getId(),
                        'category' => $cq->getName(),
                        'quantity' => $cq->getQuantity()];
                }
            }
            else
            {
                foreach( $this->categoryQuantities as $cq )
                {
                    $categoryQuantities[] = $cq;
                }
            }
        }
        return $categoryQuantities;
    }

    public function addCategoryQuantity( CategoryQuantity $categoryQuantity )
    {
        if( !empty( $this->categoryQuantities ) &&
                !$this->categoryQuantities->contains( $categoryQuantity ) )
        {
            $this->categoryQuantities->add( $categoryQuantity );
        }
    }

    public function removeCategoryQuantity( CategoryQuantity $categoryQuantity )
    {
        if( !empty( $this->categoryQuantities ) )
        {
            $this->categoryQuantities->removeElement( $categoryQuantity );
        }
    }

    public function getTimeSpans()
    {
        return empty( $this->time_spans ) ? [] : $this->time_spans->toArray();
    }

    public function setTimeSpans( $time_spans )
    {
        foreach( $time_spans as $t )
        {
            $this->addTimeSpan( $t );
        }
        return $this;
    }

    public function addTimeSpan( TimeSpan $timespan )
    {
        if( !$this->time_spans->contains( $timespan ) )
        {
            $this->time_spans->add( $timespan );
        }
    }

    public function removeTimeSpan( TimeSpan $timespan )
    {
        $this->time_spans->removeElement( $timespan );
    }

    public function getRoles()
    {
        return empty( $this->roles ) ? [] : $this->roles->toArray();
    }

    public function setRoles( $roles )
    {
        foreach( $roles as $r )
        {
            $this->addRole( $r );
        }
        return $this;
    }

    public function addRole( EventRole $role )
    {
        if( !$this->roles->contains( $role ) )
        {
            $this->roles->add( $role );
        }
    }

    public function removeRole( EventRole $role )
    {
        $this->roles->removeElement( $role );
    }

    public function getRentals()
    {
        return $this->rentals;
    }

    public function addRental( EventRental $rental )
    {
        if( !$this->rentals->contains( $rental ) )
        {
            $this->rentals->add( $rental );
        }
    }

    public function removeRental( EventRental $rental )
    {
        if( !$this->rentals->contains( $rental ) )
        {
            return;
        }

        $this->rentals->removeElement( $rental );
    }

    public function getClientEquipment()
    {
        return $this->client_equipment;
    }

    public function addClientEquipment( ClientEquipment $clientEquipment )
    {
        if( !$this->client_equipment->contains( $clientEquipment ) )
        {
            $this->client_equipment->add( $clientEquipment );
        }
    }

    public function removeClientEquipment( ClientEquipment $clientEquipment )
    {
        if( !$this->client_equipment->contains( $clientEquipment ) )
        {
            return;
        }

        $this->client_equipment->removeElement( $clientEquipment );
    }

}
