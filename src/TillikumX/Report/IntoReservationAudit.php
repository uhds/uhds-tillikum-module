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
use Vo\DateRange;

class IntoReservationAudit extends AbstractReport
{
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getDescription()
    {
        return 'Show the intersections of INTO reservations with active bookings.';
    }

    public function getFormClass()
    {
        return 'Tillikum\Form\Report\Report';
    }

    public function getName()
    {
        return 'INTO reservation audit';
    }

    public function generate()
    {
        $commonDb = \Uhds_Db::factory('common');

        $bookingsByOsuid = array();

        $sql = $commonDb->select()
            ->from(
                'into_salesforce_person',
                array('osuid', 'family_name', 'given_name', 'gender')
            )
            ->join(
                'into_salesforce_person_reservation',
                'into_salesforce_person.osuid = into_salesforce_person_reservation.person_osuid',
                array('start', 'end', 'housing_code', 'notes')
            );

        $ret = array(
            array(
                'OSU ID',
                'Name',
                'Gender',
                'Start',
                'End',
                'Housing code',
                'Notes',
                'Booking overlap type',
                'Booking assignment',
                'Booking start',
                'Booking end'
            )
        );

        foreach ($commonDb->fetchAll($sql) as $row) {
            $reservationRange = new DateRange(
                new DateTime($row['start']),
                new DateTime($row['end'])
            );

            if (!array_key_exists($row['osuid'], $bookingsByOsuid)) {
                $bookingRows = $this->em->createQuery(
                    "
                    SELECT b.start, b.end, fgc.name fgname, fc.name fname
                    FROM Tillikum\Entity\Booking\Facility\Facility b
                    JOIN TillikumX\Entity\Person\Person p WITH b.person = p
                    JOIN b.facility f
                    JOIN f.configs fc WITH fc.start BETWEEN b.start AND b.end
                    JOIN f.facility_group fg
                    JOIN fg.configs fgc WITH fgc.start BETWEEN b.start AND b.end
                    WHERE p.osuid = :osuid
                    "
                )
                    ->setParameter('osuid', $row['osuid'])
                    ->getResult();

                $bookingsByOsuid[$row['osuid']] = $bookingRows;
            }

            foreach ($bookingsByOsuid[$row['osuid']] as $booking) {
                $bookingRange = new DateRange(
                    $booking['start'],
                    $booking['end']
                );

                $overlapType = 'none';
                $bookingRoom = $booking['fname'];
                $bookingBuilding = $booking['fgname'];
                $bookingStart = $bookingRange->getStart()->format('Y-m-d');
                $bookingEnd = $bookingRange->getEnd()->format('Y-m-d');

                if (!$bookingRange->overlaps($reservationRange)) {
                    $overlapType = 'none';
                }

                if ($bookingRange->overlaps($reservationRange)) {
                    $overlapType = 'partial';
                }

                if ($bookingRange->includes($reservationRange)) {
                    $overlapType = 'full';
                }

                $ret[] = array(
                    $row['osuid'],
                    sprintf('%s, %s', $row['family_name'], $row['given_name']),
                    $row['gender'],
                    $reservationRange->getStart()->format('Y-m-d'),
                    $reservationRange->getEnd()->format('Y-m-d'),
                    $row['housing_code'],
                    $row['notes'],
                    $overlapType,
                    sprintf('%s %s', $bookingBuilding, $bookingRoom),
                    $bookingStart,
                    $bookingEnd
                );
            }
        }

        return $ret;
    }
}
