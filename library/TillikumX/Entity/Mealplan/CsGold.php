<?php
/**
 * The Tillikum Project (http://tillikum.org/)
 *
 * @link       http://tillikum.org/websvn/
 * @copyright  Copyright 2009-2012 Oregon State University (http://oregonstate.edu/)
 * @license    http://www.gnu.org/licenses/gpl-2.0-standalone.html GPLv2
 */

namespace TillikumX\Entity\Mealplan;

use Doctrine\ORM\Mapping as ORM;
use Tillikum\Entity\Entity;

/**
 * @ORM\Entity
 * @ORM\Table(name="tillikumx_mealplan_csgold", indexes={
 *     @ORM\Index(name="idx_gold_id", columns={"gold_id"})
 * })
 */
class CsGold extends Entity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Tillikum\Entity\Mealplan\Mealplan")
     * @ORM\JoinColumn(name="mealplan_id", referencedColumnName="id")
     */
    protected $mealplan;

    /**
     * @ORM\Column
     */
    protected $gold_id;

    /**
     * @ORM\Column
     */
    protected $description;
}
