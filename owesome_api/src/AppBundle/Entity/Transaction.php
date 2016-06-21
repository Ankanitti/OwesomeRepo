<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Transaction
 *
 * @ORM\Table(name="transaction")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\TransactionRepository")
 */
class Transaction
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
     * @var float
     *
     * @ORM\Column(name="sum", type="float", nullable=true)
     */
    private $sum;

    /**
     * @var bool
     *
     * @ORM\Column(name="settled", type="boolean", options={"default":0})
     */
    private $settled = 0;

    /**
     * @var Event
     *
     * @ORM\ManyToOne(targetEntity="Event", inversedBy="transactions")
     * @ORM\JoinColumn(name="event_id", referencedColumnName="id", nullable=false)
     */
    private $event;

    /**
     * @var Profile
     *
     * @ORM\ManyToOne(targetEntity="Profile", inversedBy="transactions")
     * @ORM\JoinColumn(name="profile_id", referencedColumnName="id", nullable=false)
     */
    private $profile;


    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set sum
     *
     * @param float $sum
     *
     * @return Transaction
     */
    public function setSum($sum)
    {
        $this->sum = $sum;

        return $this;
    }

    /**
     * Get sum
     *
     * @return float
     */
    public function getSum()
    {
        return $this->sum;
    }

    /**
     * Set settled
     *
     * @param boolean $settled
     *
     * @return Transaction
     */
    public function setSettled($settled)
    {
        $this->settled = $settled;

        return $this;
    }

    /**
     * Get settled
     *
     * @return bool
     */
    public function getSettled()
    {
        return $this->settled;
    }

    /**
     * Set event
     *
     * @param \AppBundle\Entity\Event $event
     *
     * @return Transaction
     */
    public function setEvent(\AppBundle\Entity\Event $event = null)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * Get event
     *
     * @return \AppBundle\Entity\Event
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Set profile
     *
     * @param \AppBundle\Entity\Profile $profile
     *
     * @return Transaction
     */
    public function setProfile(\AppBundle\Entity\Profile $profile = null)
    {
        $this->profile = $profile;

        return $this;
    }

    /**
     * Get profile
     *
     * @return \AppBundle\Entity\Profile
     */
    public function getProfile()
    {
        return $this->profile;
    }
}
