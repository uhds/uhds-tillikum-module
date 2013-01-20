<?php
/**
 * The Tillikum Project (http://tillikum.org/)
 *
 * @link       http://tillikum.org/websvn/
 * @copyright  Copyright 2009-2012 Oregon State University (http://oregonstate.edu/)
 * @license    http://www.gnu.org/licenses/gpl-2.0-standalone.html GPLv2
 */

namespace TillikumX\Entity\FacilityGroup\Config\Building;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="tillikumx_facilitygroup_config_building_hall")
 */
class Hall extends Building
{
    /**
     * {@inheritdoc}
     */
    public function isHall()
    {
        return true;
    }
}
