<?php
/**
 * The Tillikum Project (http://tillikum.org/)
 *
 * @link       http://tillikum.org/websvn/
 * @copyright  Copyright 2009-2012 Oregon State University (http://oregonstate.edu/)
 * @license    http://www.gnu.org/licenses/gpl-2.0-standalone.html GPLv2
 */

namespace TillikumX\Entity\FacilityGroup\Config\Building;

use Doctrine\Common\Collections\ArrayCollection,
    Doctrine\ORM\Mapping as ORM,
    Tillikum\Entity\FacilityGroup\Config\Building\Building as TillikumBuildingConfig;

/**
 * @ORM\Entity
 * @ORM\Table(name="tillikumx_facilitygroup_config_building", indexes={
 *     @ORM\Index(name="idx_isc_code", columns={"isc_code"})
 * })
 */
class Building extends TillikumBuildingConfig
{
    /**
     * @ORM\Column(nullable=true)
     */
    protected $email;

    /**
     * @ORM\Column(nullable=true)
     */
    protected $isc_code;

    /**
     * @ORM\ManyToMany(targetEntity="TillikumX\Entity\Facility\ServiceCenter\ServiceCenter", inversedBy="configs")
     * @ORM\JoinTable(
     *     name="tillikumx_facility_servicecenter__config_building",
     *     joinColumns={@ORM\JoinColumn(name="config_id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="servicecenter_id")}
     * )
     */
    protected $service_centers;

    public function __construct()
    {
        $this->service_centers = new ArrayCollection;
    }

    /**
     * Helper to determine if the entity is a cooperative.
     *
     * @return bool
     */
    public function isCooperative()
    {
        return false;
    }

    /**
     * Helper to determine if the entity is a residence hall.
     *
     * @return bool
     */
    public function isHall()
    {
        return false;
    }
}
