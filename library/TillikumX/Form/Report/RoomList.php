<?php
/**
 * The Tillikum Project (http://tillikum.org/)
 *
 * @link       http://tillikum.org/websvn/
 * @copyright  Copyright 2009-2012 Oregon State University (http://oregonstate.edu/)
 * @license    http://www.gnu.org/licenses/gpl-2.0-standalone.html GPLv2
 */

namespace TillikumX\Form\Report;

use DateTime;
use Doctrine\ORM\EntityManager;
use Tillikum\Form\Report\Report as ReportForm;
use Tillikum\ORM\EntityManagerAwareInterface;

class RoomList extends ReportForm implements EntityManagerAwareInterface
{
    protected $em;

    public function init()
    {
        parent::init();

        $date = new \Tillikum_Form_Element_Date(
            'date',
            array(
                'label' => 'For which date would you like to see rooms?',
                'required' => true,
            )
        );

        $facilityGroups = new \Zend_Form_Element_Multiselect(
            'facility_groups',
            array(
                'label' => 'Which buildings would you like to see?',
                'multiOptions' => array(),
                'required' => true,
            )
        );

        $this->addElements(
            array(
                $date,
                $facilityGroups,
            )
        );
    }

    public function setEntityManager(EntityManager $em)
    {
        $this->em = $em;

        $facilityGroups = $this->em->createQuery(
            "
            SELECT fg.id, bc.name
            FROM Tillikum\Entity\FacilityGroup\Config\Building\Building bc
            JOIN bc.facility_group fg
            WHERE :now BETWEEN bc.start AND bc.end
            ORDER BY bc.name
            "
        )
            ->setParameter('now', new DateTime())
            ->getResult();

        $facilityGroupOptions = array();
        foreach ($facilityGroups as $facilityGroup) {
            $facilityGroupOptions[$facilityGroup['id']] = $facilityGroup['name'];
        }

        $this->getElement('facility_groups')->setMultiOptions(
            $facilityGroupOptions
        );

        $this->getElement('facility_groups')->setAttribs(
            array(
                'size' => count($facilityGroupOptions),
            )
        );

        return $this;
    }
}
