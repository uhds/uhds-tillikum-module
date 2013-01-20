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

class MixedGender extends AbstractReport
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getDescription()
    {
        return 'All rooms that have mixed genders.';
    }

    public function getFormClass()
    {
        return 'TillikumX\Form\Report\MixedGender';
    }

    public function getName()
    {
        return 'Mixed genders';
    }

    public function generate()
    {
        $parameters = $this->getParameters();

        $date = new DateTime($parameters['date']);

        $rows = $this->em->createQuery(
            "
            SELECT p.osuid, p.given_name, p.family_name, p.gender, p.birthdate,
                   b.start, b.end,
                   r.id, rc.name rname, 
                   s.id sid,
                   bc.name bldname
            FROM TillikumX\Entity\Person\Person p
            JOIN p.bookings b WITH :date BETWEEN b.start AND b.end
            JOIN Tillikum\Entity\Facility\Room\Room r WITH r = b.facility
            JOIN Tillikum\Entity\Facility\Config\Room\Room rc WITH rc.facility = r AND b.start BETWEEN rc.start AND rc.end
            LEFT JOIN rc.suite s
            JOIN Tillikum\Entity\FacilityGroup\Building\Building bld WITH bld = r.facility_group
            JOIN bld.configs bc WITH b.start BETWEEN bc.start AND bc.end
            "
        )
            ->setParameter('date', $date)
            ->getResult();

        $ret = array(
            array(
                'OSU ID',
                'Last name',
                'First name',
                'Gender',
                'Age (as of ' . $date->format('n/j/Y') . ')',
                'Building',
                'Room',
            )
        );

        $facilitiesKeyedById = array();
        $facilitiesKeyedBySuite = array();
        foreach ($rows as $row) {
            $facilitiesKeyedById[$row['id']][] = array(
                'id' => $row['id'],
                'bldname' => $row['bldname'],
                'rname' => $row['rname'],
                'sid' => $row['sid'],
                'range' => new DateRange($row['start'], $row['end']),
                'gender' => $row['gender'],
                'birthdate' => $row['birthdate'],
                'osuid' => $row['osuid'],
                'given_name' => $row['given_name'],
                'family_name' => $row['family_name']
            );

            if ($row['sid']) {
                $facilitiesKeyedBySuite[$row['sid']][] = array(
                    'id' => $row['id'],
                    'bldname' => $row['bldname'],
                    'rname' => $row['rname'],
                    'sid' => $row['sid'],
                    'range' => new DateRange($row['start'], $row['end']),
                    'gender' => $row['gender'],
                    'birthdate' => $row['birthdate'],
                    'osuid' => $row['osuid'],
                    'given_name' => $row['given_name'],
                    'family_name' => $row['family_name']
                );
            }
        }

        /*
         * Algorithm is as follows:
         *
         * For each new facility we encounter, take the first booking and
         * determine all overlapping bookings in both the facility and
         * the suite (if a suite exists). Build up a gender match specification
         * that requires all overlapping bookings to match. When we have run out
         * of overlapping bookings to check, execute the specification. If it
         * fails, we dump all overlapping bookings to the report. Otherwise, we
         * keep looking.
         */
        $mismatchedBookings = array();
        foreach ($facilitiesKeyedById as $facilityId => $bookings) {
            if (count($bookings) < 2) {
                continue;
            }

            $firstBooking = $bookings[0];
            $workingBookingSet = array($firstBooking);

            foreach (new LimitIterator(new ArrayIterator($bookings), 1) as $booking) {
                if ($firstBooking['range']->overlaps($booking['range'])) {
                    $workingBookingSet[] = $booking;
                }
            }

            if ($firstBooking['sid']) {
                $suiteBookings = $facilitiesKeyedBySuite[$firstBooking['sid']];
                foreach ($suiteBookings as $suiteBooking) {
                    // Skip the 'current' facility (it's the one we're working on)
                    if ($facilityId === $suiteBooking['id']) {
                        continue;
                    }

                    if ($firstBooking['range']->overlaps($suiteBooking['range'])) {
                        $workingBookingSet[] = $suiteBooking;
                    }
                }
            }

            if (count($workingBookingSet) > 1) {
                foreach (new LimitIterator(new ArrayIterator($workingBookingSet), 1) as $workingBooking) {
                    if (!isset($genderSpec)) {
                        $genderSpec = new \Tillikum\Specification\Specification\GenderMatch($workingBooking['gender']);
                    } else {
                        $genderSpec = $genderSpec->andSpec(new \Tillikum\Specification\Specification\GenderMatch($workingBooking['gender']));
                    }
                }

                if (!$genderSpec->isSatisfiedBy($workingBookingSet[0]['gender'])) {
                    $mismatchedBookings = array_merge(
                        $mismatchedBookings,
                        $workingBookingSet
                    );
                }

                unset($genderSpec);
            }
        }

        $seen = array();
        $mismatchedBookings = array_values(
            array_filter(
                $mismatchedBookings,
                function ($v) use (&$seen) {
                    foreach ($seen as $s) {
                        if ($v['id'] === $s['id']
                            && $v['range']->compareTo($s['range']) === 0
                            && $v['osuid'] === $s['osuid']
                        ) {
                            return false;
                        }
                    }

                    $seen[] = $v;

                    return true;
                }
            )
        );

        foreach ($mismatchedBookings as $booking) {
            $ret[] = array(
                $booking['osuid'],
                $booking['family_name'],
                $booking['given_name'],
                $booking['gender'],
                date_diff($booking['birthdate'], $date)->y,
                $booking['bldname'],
                $booking['rname'],
            );
        }

        return $ret;
    }
}
