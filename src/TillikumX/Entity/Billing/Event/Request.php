<?php
/**
 * The Tillikum Project (http://tillikum.org/)
 *
 * @link       http://tillikum.org/websvn/
 * @copyright  Copyright 2009-2012 Oregon State University (http://oregonstate.edu/)
 * @license    http://www.gnu.org/licenses/gpl-2.0-standalone.html GPLv2
 */

namespace TillikumX\Entity\Billing\Event;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Tillikum\Entity\Entity;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="tillikumx_billing_event_request", indexes={
 *     @ORM\Index(name="idx_state", columns={"state"})
 * })
 */
class Request extends Entity
{
    const STATE_SUBMITTED = 'submitted';
    const STATE_REJECTED = 'rejected';
    const STATE_PROCESSED = 'processed';

    protected static $stateDescriptions = array(
        self::STATE_SUBMITTED => 'Submitted',
        self::STATE_REJECTED => 'Rejected',
        self::STATE_PROCESSED => 'Processed successfully',
    );

    /**
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     * @ORM\Id
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Tillikum\Entity\Person\Person")
     * @ORM\JoinColumn(name="person_id", referencedColumnName="id")
     */
    protected $person;

    /**
     * @ORM\ManyToOne(targetEntity="Tillikum\Entity\Billing\Rule\Rule")
     * @ORM\JoinColumn(name="rule_id", referencedColumnName="id")
     */
    protected $rule;

    /**
     * @ORM\Column
     */
    protected $currency;

    /**
     * @ORM\Column(type="decimal", precision=18, scale=4)
     */
    protected $amount;

    /**
     * @ORM\Column(type="text")
     */
    protected $description;

    /**
     * @ORM\Column
     */
    protected $state;

    /**
     * @ORM\Column(type="utcdatetime")
     */
    protected $created_at;

    /**
     * @ORM\Column
     */
    protected $created_by;

    /**
     * @ORM\Column(type="utcdatetime")
     */
    protected $updated_at;

    /**
     * @ORM\Column
     */
    protected $updated_by;

    /**
     * @ORM\PrePersist
     */
    public function prePersistListener()
    {
        $this->created_at = new DateTime();
        $this->updated_at = new DateTime();
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdateListener()
    {
        $this->updated_at = new DateTime();
    }

    public static function getStateDescriptions()
    {
        return self::$stateDescriptions;
    }
}
