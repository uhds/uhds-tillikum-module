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
use DateTimeZone;
use Doctrine\ORM\EntityManager;
use Tillikum\Report\AbstractReport;

class VacatedRooms extends AbstractReport
{
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getDescription()
    {
        return 'Rooms whose occupants have left and are ready to be cleaned based on check-out dates.';
    }

    public function getFormClass()
    {
        return 'TillikumX\Form\Report\VacatedRooms';
    }

    public function getName()
    {
        return 'Vacated rooms';
    }

    public function generate()
    {
        $parameters = $this->getParameters();

        $rangeStart = new DateTime($parameters['range_start']);
        $rangeEnd = new DateTime($parameters['range_end']);
        $rangeEnd->modify('+1 day');

        $rows = $this->em->createQuery(
            "
            SELECT MAX(bc.checkout_at) latest_checkout,
                   COUNT(bc.checkout_at) checkout_count,
                   COUNT(b.id) booking_count,
                   fc.name fname, fc.capacity,
                   fgc.name fgname
            FROM Tillikum\Entity\Booking\Facility\Facility b
            LEFT JOIN Tillikum\Entity\Booking\Facility\Facility bc
                WITH b = bc AND
                     bc.checkout_at >= :rangeStart AND
                     bc.checkout_at < :rangeEnd
            JOIN b.facility f
            JOIN f.configs fc
            JOIN f.facility_group fg
            JOIN fg.configs fgc
            WHERE b.start <= :rangeEnd AND
                  b.end >= :rangeStart AND
                  fc.start <= b.start AND
                  fc.end >= b.start AND
                  fgc.start <= b.start AND
                  fgc.end >= b.start
            GROUP BY f.id
            HAVING checkout_count >= booking_count
            "
        )
            ->setParameter('rangeStart', $rangeStart)
            ->setParameter('rangeEnd', $rangeEnd)
            ->getResult();

        $ret = array(
            array(
                'Latest check-out timestamp',
                'Facility group',
                'Facility',
                'Capacity',
                'Booking count',
            )
        );

        foreach ($rows as $row) {
            $ret[] = array(
                date('Y-m-d H:i:s', date_create($row['latest_checkout'] . 'Z')->format('U')),
                $row['fgname'],
                $row['fname'],
                $row['capacity'],
                $row['booking_count'],
            );
        }

        return $ret;
    }
}
