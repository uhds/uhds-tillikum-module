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

class RoomList extends AbstractReport
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getDescription()
    {
        return 'List rooms in a given facility group.';
    }

    public function getFormClass()
    {
        return 'TillikumX\Form\Report\RoomList';
    }

    public function getName()
    {
        return 'Room list';
    }

    public function generate()
    {
        $parameters = $this->getParameters();

        $date = new DateTime($parameters['date']);
        $facilityGroupIds = $parameters['facility_groups'];

        $result = $this->em->createQuery(
            "
            SELECT r.id, rc.name rname, rc.capacity, rc.gender, rc.floor, rc.section,
                   CASE WHEN COUNT(tag_ra.id) > 0 THEN 'Y' ELSE 'N' END is_ra,
                   fgc.name fgname,
                   s.name sname,
                   t.name tname
            FROM Tillikum\Entity\Facility\Room\Room r
            JOIN Tillikum\Entity\Facility\Config\Room\Room rc WITH r = rc.facility
            JOIN rc.type t
            JOIN r.facility_group fg
            JOIN fg.configs fgc
            LEFT JOIN rc.suite s
            LEFT JOIN rc.tags tag_ra WITH tag_ra.id = 'ra'
            WHERE fg.id IN (:facilityGroupIds) AND
                  :date BETWEEN rc.start AND rc.end AND
                  :date BETWEEN fgc.start AND fgc.end
            GROUP BY r.id
            ORDER BY fgname, rname
            "
        )
            ->setParameter('facilityGroupIds', $facilityGroupIds)
            ->setParameter('date', $date)
            ->getResult();

        $ret = [
            [
                'Room group',
                'Room',
                'Room capacity',
                'Room gender',
                'Room type',
                'Floor',
                'Section',
                'Suite',
                'RA room?',
            ]
        ];

        foreach ($result as $row) {
            $ret[] = [
                $row['fgname'],
                $row['rname'],
                $row['capacity'],
                $row['gender'],
                $row['tname'],
                $row['floor'],
                $row['section'],
                $row['sname'],
                $row['is_ra'],
            ];
        }

        return $ret;
    }
}
