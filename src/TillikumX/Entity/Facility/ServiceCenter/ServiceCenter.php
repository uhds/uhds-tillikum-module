<?php
/**
 * The Tillikum Project (http://tillikum.org/)
 *
 * @link       http://tillikum.org/websvn/
 * @copyright  Copyright 2009-2012 Oregon State University (http://oregonstate.edu/)
 * @license    http://www.gnu.org/licenses/gpl-2.0-standalone.html GPLv2
 */

namespace TillikumX\Entity\Facility\ServiceCenter;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Tillikum\Entity\Entity;

/**
 * @ORM\Entity
 * @ORM\Table(name="tillikumx_facility_servicecenter", indexes={
 *     @ORM\Index(name="idx_code", columns={"code"})
 * })
 */
class ServiceCenter extends Entity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @ORM\ManyToMany(targetEntity="TillikumX\Entity\FacilityGroup\Config\Building\Building", mappedBy="service_centers")
     */
    protected $configs;

    /**
     * @ORM\Column
     */
    protected $code;

    /**
     * @ORM\Column
     */
    protected $name;

    /**
     * @ORM\Column(nullable=true)
     */
    protected $email;

    /**
     * @ORM\Column(nullable=true)
     */
    protected $phone;

    /**
     * @ORM\Column(nullable=true)
     */
    protected $fax;

    /**
     * @ORM\Column(nullable=true)
     */
    protected $hours;

    public function __construct()
    {
        $this->configs = new ArrayCollection;
    }
}
