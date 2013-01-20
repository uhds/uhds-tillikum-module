<?php
/**
 * The Tillikum Project (http://tillikum.org/)
 *
 * @link       http://tillikum.org/websvn/
 * @copyright  Copyright 2009-2012 Oregon State University (http://oregonstate.edu/)
 * @license    http://www.gnu.org/licenses/gpl-2.0-standalone.html GPLv2
 */

namespace TillikumX\Report;

use DateTime;
use Doctrine\ORM\EntityManager;
use Tillikum\Report\AbstractReport;

class FacilityList extends AbstractReport
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getDescription()
    {
        return 'List facilities in a given facility group.';
    }

    public function getFormClass()
    {
        return 'TillikumX\Form\Report\FacilityList';
    }

    public function getName()
    {
        return 'Facility list';
    }

    public function generate()
    {
        $parameters = $this->getParameters();

        $date = new DateTime($parameters['date']);
        $facilityGroupIds = $parameters['facility_groups'];

        $facilities = $this->em->createQuery(
            "
            SELECT f.id, fc.name fname, fc.capacity, fc.gender,
                   CASE WHEN COUNT(tag_ra.id) > 0 THEN 'Y' ELSE 'N' END is_ra,
                   fgc.name fgname
            FROM Tillikum\Entity\Facility\Facility f
            JOIN f.configs fc
            LEFT JOIN fc.tags tag_ra WITH tag_ra.id = 'ra'
            JOIN f.facility_group fg
            JOIN fg.configs fgc
            WHERE fg.id IN (:facilityGroupIds) AND 
                  fc.start <= :date AND fc.end >= :date AND
                  fgc.start <= :date AND fgc.end >= :date
            GROUP BY f.id
            ORDER BY fgname, fname
            "
        )
            ->setParameter('facilityGroupIds', $facilityGroupIds)
            ->setParameter('date', $date)
            ->getResult();

        $ret = array(
            array(
                'Facility group',
                'Facility',
                'Facility capacity',
                'Facility gender',
                'RA Facility?',
            )
        );

        foreach ($facilities as $row) {
            $ret[] = array(
                $row['fgname'],
                $row['fname'],
                $row['capacity'],
                $row['gender'],
                $row['is_ra'],
            );
        }

        return $ret;
    }
}
