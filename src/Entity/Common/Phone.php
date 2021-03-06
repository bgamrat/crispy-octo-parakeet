<?php

Namespace App\Entity\Common;

use App\Entity\Common\PhoneType;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Traits\Comment;
use App\Entity\Traits\Id;

/**
 * @ORM\Entity
 * @ORM\Table(name="phone")
 * @Gedmo\Loggable
 */
class Phone
{
    use 
     Id,
            Comment;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @ORM\ManyToOne(targetEntity="PhoneType")
     * @ORM\JoinColumn(name="type_id", referencedColumnName="id")
     * @ORM\OrderBy({"type" = "ASC"})
     */
    private $type;
    /**
     * @Assert\NotBlank()
     * @Assert\Regex(pattern="/^[0-9x\.\,\ \+\(\)-]{2,24}$/", message="error.invalid_phone_number")
     * @ORM\Column(type="string", length=24, name="phone", nullable=false)
     */
    private $phone;

    /**
     * Set type
     *
     * @param int $type
     *
     * @return Phone
     */
    public function setType( $type )
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set phone
     *
     * @param string $phone
     *
     * @return Phone
     */
    public function setPhone( $phone )
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

}
