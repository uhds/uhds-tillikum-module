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

class MailboxNotAssigned extends ReportForm
{
    /**
     * Excluded buildings
     *
     * Buildings excluded from the building dropdown due to not being relevant
     * to assigned mailboxes.
     *
     * @var array
     */
    protected static $excludedBuildingIds = array(
        'd7e9b0536c8fec14ea94e94cb0e63eb9', // APT
        'edc942d7337826aaea7dd4ce4b7d47e9', // AVE
        'b3662bf2d638d950107fe288e1d5055f', // AZL
        '84afa3f6f47f85df75b43079e27631e3', // BLS
        '68679e6cb5cb13f2a41a88135621941c', // DIX
        '59ef4021cee6f7899a8c1b2836511798', // FIN
        'bb26e1550776cd4dff15fdc2efaaa3ed', // HAL
        '1011895cf8086e319a20445b38eaf3c0', // HOME
        'be74ff317e41fb8234bb6f518d372a83', // OXF
        '8e77483e370b96faa81a1957bb170e1d', // LLC
    );

    protected $em;

    public function init()
    {
        parent::init();

        $date = new \Tillikum_Form_Element_Date(
            'date',
            array(
                'label' => 'What date do you want to use?',
                'required' => true,
            )
        );

        $facilityGroups = new \Zend_Form_Element_Multiselect(
            'facility_groups',
            array(
                'label' => 'Which facility groups would you like to check?',
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
            SELECT fg.id, c.name
            FROM Tillikum\Entity\FacilityGroup\Config\Config c
            JOIN c.facility_group fg
            WHERE c.start <= :now and c.end >= :now AND
                  fg.id NOT IN (:excludedBuildingIds)
            ORDER BY c.name
            "
        )
            ->setParameter('excludedBuildingIds', self::$excludedBuildingIds)
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
