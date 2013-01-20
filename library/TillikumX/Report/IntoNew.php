<?php
/**
 * The Tillikum Project (http://tillikum.org/)
 *
 * @link       http://tillikum.org/websvn/
 * @copyright  Copyright 2009-2012 Oregon State University (http://oregonstate.edu/)
 * @license    http://www.gnu.org/licenses/gpl-2.0-standalone.html GPLv2
 */

namespace TillikumX\Report;

use ArrayIterator;
use DateTime;
use Doctrine\ORM\EntityManager;
use LimitIterator;
use Tillikum\Report\AbstractReport;
use Vo\DateRange;

class IntoNew extends AbstractReport
{
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getDescription()
    {
        return 'List INTO reservations starting on or after a given date.';
    }

    public function getFormClass()
    {
        return 'TillikumX\Form\Report\IntoNew';
    }

    public function getName()
    {
        return 'INTO new reservations';
    }

    public function generate()
    {
        $commonDb = \Uhds_Db::factory('common');

        $bookingsById = array();

        $parameters = $this->getParameters();

        $bookingDate = new DateTime($parameters['booking_date']);
        $reservationDate = new DateTime($parameters['reservation_date']);

        $reservationRows = $commonDb->fetchAll(
            "
            SELECT p.osuid, p.given_name, p.family_name, p.gender,
                   r.start, r.end, r.housing_code
            FROM into_salesforce_person p
            JOIN into_salesforce_person_reservation r ON p.osuid = r.person_osuid
            WHERE r.start >= {$commonDb->quote($reservationDate->format('Y-m-d'))}
            GROUP BY p.osuid
            HAVING (
                SELECT COUNT(*)
                FROM into_salesforce_person p2
                JOIN into_salesforce_person_reservation r2 ON p2.osuid = r2.person_osuid
                WHERE r2.start < {$commonDb->quote($reservationDate->format('Y-m-d'))} AND
                      p.osuid = p2.osuid
            ) = 0
            ORDER BY r.start
            "
        );

        $ret = array(
            array(
                'OSU ID',
                'Last name',
                'First name',
                'Gender',
                'Start',
                'End',
                'Housing code',
                'Facility group',
                'Facility',
                'Booking start',
                'Booking end'
            )
        );

        foreach ($reservationRows as $row) {
            $reservationRange = new DateRange(
                new DateTime($row['start']),
                new DateTime($row['end'])
            );

            $bookingsById[$row['osuid']] = $this->em->createQuery(
                "
                SELECT b.start, b.end, fc.name fname, fgc.name fgname
                FROM Tillikum\Entity\Booking\Facility\Facility b
                LEFT JOIN TillikumX\Entity\Person\Person p WITH b.person = p
                JOIN b.facility f
                JOIN f.configs fc
                JOIN f.facility_group fg
                JOIN fg.configs fgc
                WHERE b.start <= :date AND b.end >= :date AND
                      fc.start <= b.start AND fc.end >= b.end AND
                      fgc.start <= b.start AND fgc.end >= b.end AND
                      p.osuid = :osuid
                "
            )
                ->setParameter('date', $parameters['booking_date'])
                ->setParameter('osuid', $row['osuid'])
                ->getResult();

            if (empty($bookingsById[$row['osuid']])) {
                $ret[] = array(
                    $row['osuid'],
                    $row['family_name'],
                    $row['given_name'],
                    $row['gender'],
                    $reservationRange->getStart()->format('n/j/Y'),
                    $reservationRange->getEnd()->format('n/j/Y'),
                    $row['housing_code'],
                    '',
                    '',
                    '',
                    ''
                );
            } else {
                $iter = new LimitIterator(
                    new ArrayIterator($bookingsById[$row['osuid']]),
                    0,
                    1
                );

                foreach ($iter as $booking) {
                    $bookingRange = new DateRange(
                        $booking['start'],
                        $booking['end']
                    );

                    $ret[] = array(
                        $row['osuid'],
                        $row['family_name'],
                        $row['given_name'],
                        $row['gender'],
                        $reservationRange->getStart()->format('n/j/Y'),
                        $reservationRange->getEnd()->format('n/j/Y'),
                        $row['housing_code'],
                        $booking['fgname'],
                        $booking['fname'],
                        $bookingRange->getStart()->format('n/j/Y'),
                        $bookingRange->getEnd()->format('n/j/Y')
                    );
                }
            }
        }

        return $ret;
    }
}
