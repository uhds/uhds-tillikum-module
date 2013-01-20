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

class OccupancyCounts extends AbstractReport
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getDescription()
    {
        return 'Output occupancy counts in all spaces on a given date.';
    }

    public function getFormClass()
    {
        return 'TillikumX\Form\Report\OccupancyCounts';
    }

    public function getName()
    {
        return 'Occupancy counts';
    }

    public function generate()
    {
        $parameters = $this->getParameters();
        $date = new DateTime($parameters['date']);

        $roomtypes = $this->em->createQuery(
            "
            SELECT COUNT(b) total, t.name category, fgc.name building
            FROM Tillikum\Entity\Booking\Facility\Facility b
            JOIN Tillikum\Entity\Facility\Room\Room r WITH b.facility = r
            JOIN r.facility_group fg
            JOIN fg.configs fgc
            JOIN Tillikum\Entity\Facility\Config\Room\Room rc WITH r = rc.facility
            JOIN rc.type t
            WHERE :date BETWEEN b.start AND b.end AND
                  :date BETWEEN rc.start AND rc.end AND
                  :date BETWEEN fgc.start AND fgc.end 
            GROUP BY fg, t
            "
        )
            ->setParameter('date', $date)
            ->getResult();

        $staff = $this->em->createQuery(
            "
            SELECT COUNT(b) total, CONCAT('Staff (', CONCAT(t.name, ')')) category, fgc.name building
            FROM Tillikum\Entity\Booking\Facility\Facility b
            JOIN Tillikum\Entity\Facility\Room\Room r WITH b.facility = r
            JOIN r.facility_group fg
            JOIN fg.configs fgc
            JOIN Tillikum\Entity\Facility\Config\Room\Room rc WITH r = rc.facility
            JOIN rc.type t
            JOIN rc.tags ftag_ra WITH ftag_ra.id = 'ra'
            WHERE :date BETWEEN b.start AND b.end AND
                  :date BETWEEN rc.start AND rc.end AND
                  :date BETWEEN fgc.start AND fgc.end
            GROUP BY fg, t
            "
        )
            ->setParameter('date', $date)
            ->getResult();

        $ret = array(
            array(
                'Building',
                'Category',
                'Count'
            )
        );

        foreach ($roomtypes as $room) {
            $ret[] = array(
                $room['building'],
                $room['category'],
                $room['total'],
            );
        }

        foreach ($staff as $member) {
            $ret[] = array(
                $member['building'],
                $member['category'],
                $member['total'],
            );
        }

        $header = array_shift($ret);
        usort(
            $ret,
            function ($a, $b) {
                return strnatcmp($a[0] . $a[1], $b[0] . $b[1]);
            }
        );
        array_unshift($ret, $header);

        return $ret;
    }
}
